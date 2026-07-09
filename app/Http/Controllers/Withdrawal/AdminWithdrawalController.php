<?php

namespace App\Http\Controllers\Withdrawal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Withdrawal;
use App\Models\UserWallet;
use App\Http\Controllers\Wallet\WalletTransactionController;
use App\Services\SquadcoService;
use Exception;

class AdminWithdrawalController extends Controller
{
    // List pending withdrawals (admin)
    public function index(Request $request)
    {
        $perPage = min((int) $request->get('per_page', 20), 200);
        $query = Withdrawal::orderBy('created_at', 'desc');
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $list = $query->paginate($perPage);
        return response()->json(['success' => true, 'data' => $list]);
    }

    // Approve a pending withdrawal and trigger payout
    public function approve(Request $request, Withdrawal $withdrawal)
    {
        if ($withdrawal->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Only pending withdrawals can be approved.'], 400);
        }

        try {
            DB::transaction(function () use ($withdrawal, $request) {
                $userId = $withdrawal->user_id;

                // Attempt payout via Squadco using stored rider bank details.
                $squadco = app(SquadcoService::class);
                $user = $withdrawal->user()->first();
                $profile = $user ? $user->riderProfile()->first() : null;
                $bankAccount = $profile ? $profile->bank_account_number : null;
                $bankCode = $profile ? $profile->bank_code : null;
                if (! $bankAccount || ! $bankCode) {
                    throw new Exception('No bank details configured for this account.');
                }

                $reference = 'wd_admin_' . $withdrawal->id . '_' . time();

                $payload = [
                    'account_number' => $bankAccount,
                    'bank_code' => $bankCode,
                    'amount' => $withdrawal->amount,
                    'narration' => 'Rider withdrawal (admin approved)',
                    'reference' => $reference,
                ];

                try {
                    $resp = $squadco->payoutToBank($payload);
                    \Illuminate\Support\Facades\Log::info('Squadco payout response', ['resp' => $resp]);
                    $isSuccess = false;
                    if (is_array($resp) || is_object($resp)) {
                        if (!empty($resp)) {
                            $isSuccess = true;
                        }
                        if (!empty($resp['status']) && $resp['status'] === 'success') {
                            $isSuccess = true;
                        }
                        if (!empty($resp['data']['status']) && $resp['data']['status'] === 'processed') {
                            $isSuccess = true;
                        }
                    } elseif (is_string($resp) && $resp === 'success') {
                        $isSuccess = true;
                    }

                    if ($isSuccess) {
                        $withdrawal->status = 'approved';
                    } else {
                        $withdrawal->status = 'failed';
                        // Refund only the requested amount (platform keeps the fee)
                        $wallet = UserWallet::where('user_id', $userId)->lockForUpdate()->first();
                        if ($wallet) {
                            $wallet->balance += $withdrawal->amount;
                            $wallet->save();
                            WalletTransactionController::save([
                                'user_id' => $userId,
                                'amount' => $withdrawal->amount,
                                'transaction_type' => 'credit',
                                'purpose' => 'withdrawal_refund',
                            ]);
                        }
                    }
                    $withdrawal->reference = $reference;
                    $withdrawal->provider_response = is_array($resp) || is_object($resp) ? json_decode(json_encode($resp), true) : ['raw' => (string) $resp];
                    $withdrawal->admin_note = $request->input('admin_note', $withdrawal->admin_note);
                    $withdrawal->save();

                    // Mark associated platform fee as collected (platform keeps fee on both success and failure)
                    \App\Models\PlatformFee::where('withdrawal_id', $withdrawal->id)->update(['collected' => true]);
                } catch (Exception $e) {
                    // On exception, mark failed and refund the requested amount
                    $withdrawal->status = 'failed';
                    $withdrawal->admin_note = $request->input('admin_note', $withdrawal->admin_note);
                    $withdrawal->save();
                    $wallet = UserWallet::where('user_id', $userId)->lockForUpdate()->first();
                    if ($wallet) {
                        $wallet->balance += $withdrawal->amount;
                        $wallet->save();
                        WalletTransactionController::save([
                            'user_id' => $userId,
                            'amount' => $withdrawal->amount,
                            'transaction_type' => 'credit',
                            'purpose' => 'withdrawal_refund',
                        ]);
                    }
                }
            });

            return response()->json(['success' => true, 'message' => 'Withdrawal processed.', 'data' => $withdrawal->fresh()]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    // Decline a pending withdrawal and refund if reserved
    public function decline(Request $request, Withdrawal $withdrawal)
    {
        if ($withdrawal->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Only pending withdrawals can be declined.'], 400);
        }

        try {
            DB::transaction(function () use ($withdrawal, $request) {
                $userId = $withdrawal->user_id;
                $wallet = UserWallet::where('user_id', $userId)->lockForUpdate()->first();
                if (! $wallet) {
                    throw new Exception('Wallet not found.');
                }

                // Refund (if funds were previously reserved they must be added back).
                $wallet->balance += $withdrawal->amount;
                $wallet->save();
                WalletTransactionController::save([
                    'user_id' => $userId,
                    'amount' => $withdrawal->amount,
                    'transaction_type' => 'credit',
                    'purpose' => 'withdrawal_declined_refund',
                ]);

                $withdrawal->status = 'declined';
                $withdrawal->admin_note = $request->input('admin_note', $withdrawal->admin_note);
                $withdrawal->save();
            });

            return response()->json(['success' => true, 'message' => 'Withdrawal declined and refunded.', 'data' => $withdrawal->fresh()]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
