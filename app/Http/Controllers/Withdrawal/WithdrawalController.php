<?php

namespace App\Http\Controllers\Withdrawal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\AppNotification;
use App\Models\Withdrawal;
use App\Models\PlatformFee;
use App\Models\UserWallet;
use App\Http\Controllers\Wallet\WalletTransactionController;
use App\Services\SquadcoService;
use Exception;

class WithdrawalController extends Controller
{
    /**
     * Request a withdrawal — rider uses stored bank details on profile.
     * POST /api/withdrawals
     */
    public function request(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', 'min:1'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $amount = (float) $request->input('amount');
        // Flat withdrawal fee (platform). Configurable via env `WITHDRAWAL_FEE`.
        $fee = (float) (config('payments.withdrawal_fee') ?? env('WITHDRAWAL_FEE', 50));

        // Check wallet and balance inside a DB transaction with row lock
        try {
            $withdrawal = DB::transaction(function () use ($user, $amount, $request, $fee) {
                $wallet = UserWallet::where('user_id', $user->id)->lockForUpdate()->first();
                if (! $wallet) {
                    throw new Exception('Wallet not found for this account.');
                }

                $totalReserve = $amount + $fee;
                if ($wallet->balance < $totalReserve) {
                    throw new Exception('Insufficient wallet balance.');
                }

                // Debit wallet immediately to reserve funds for withdrawal + fee
                $wallet->balance = $wallet->balance - $totalReserve;
                $wallet->save();

                // Record two transactions: withdrawal amount and platform fee
                WalletTransactionController::save([
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'transaction_type' => 'debit',
                    'purpose' => 'withdrawal',
                ]);
                if ($fee > 0) {
                    WalletTransactionController::save([
                        'user_id' => $user->id,
                        'amount' => $fee,
                        'transaction_type' => 'debit',
                        'purpose' => 'withdrawal_fee',
                    ]);
                }

                // Create withdrawal record with pending status (store fee separately)
                $wd = Withdrawal::create([
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'fee' => $fee,
                    'status' => 'pending',
                    'admin_note' => $request->input('note', ''),
                ]);

                // Track platform fee record (not yet collected until admin processes)
                PlatformFee::create([
                    'withdrawal_id' => $wd->id,
                    'amount' => $fee,
                    'collected' => false,
                    'description' => 'Withdrawal fee',
                ]);

                return $wd;
            });

            AppNotification::create([
                'user_id' => $user->id,
                'type' => 'withdrawal_requested',
                'payload' => [
                    'title' => 'Withdrawal Requested',
                    'body' => "Your withdrawal request for ₦{$amount} has been submitted and is pending approval.",
                ],
                'is_read' => false,
            ]);

            // If admin auto-approve is enabled, process payout now
            $auto = config('services.squadco.auto_approve_withdrawals', env('SQUADCO_AUTO_APPROVE', false));
            if ($auto) {
                try {
                    $squadco = app(SquadcoService::class);
                    $profile = $user->riderProfile()->first();
                    $bankAccount = $profile ? $profile->bank_account_number : null;
                    $bankCode = $profile ? $profile->bank_code : null;
                    if (! $bankAccount || ! $bankCode) {
                        throw new Exception('No bank details configured for this account.');
                    }

                    $reference = 'wd_' . $withdrawal->id . '_' . time();
                    $payload = [
                        'account_number' => $bankAccount,
                        'bank_code' => $bankCode,
                        'amount' => $amount,
                        'narration' => 'Rider withdrawal',
                        'reference' => $reference,
                    ];

                    $resp = $squadco->payoutToBank($payload);
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
                        $this->refundWithdrawal($withdrawal, $amount, $user);
                    }
                    $withdrawal->reference = $reference;
                    $withdrawal->provider_response = is_array($resp) || is_object($resp) ? json_decode(json_encode($resp), true) : ['raw' => (string) $resp];
                    $withdrawal->save();
                } catch (Exception $e) {
                    $withdrawal->status = 'failed';
                    $withdrawal->save();
                    $this->refundWithdrawal($withdrawal, $amount, $user);
                }
            }

            return response()->json(['success' => true, 'data' => $withdrawal], 201);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Refund a withdrawal amount back to the user's wallet.
     */
    protected function refundWithdrawal(Withdrawal $withdrawal, float $amount, $user): void
    {
        DB::transaction(function () use ($withdrawal, $amount, $user) {
            $wallet = UserWallet::where('user_id', $user->id)->lockForUpdate()->first();
            if (! $wallet) {
                throw new Exception('Wallet not found for this account.');
            }

            $wallet->balance = $wallet->balance + $amount;
            $wallet->save();

            WalletTransactionController::save([
                'user_id' => $user->id,
                'amount' => $amount,
                'transaction_type' => 'credit',
                'purpose' => 'withdrawal_refund',
            ]);
        });
    }

    /**
     * List withdrawals for authenticated user
     */
    public function myWithdrawals(Request $request): JsonResponse
    {
        $perPage = min((int) $request->get('per_page', 15), 100);
        $list = Withdrawal::where('user_id', $request->user()->id)->orderBy('created_at', 'desc')->paginate($perPage);
        return response()->json(['success' => true, 'data' => $list]);
    }
}
