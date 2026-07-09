<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Models\EscrowTransaction;
use App\Models\Job;
use App\Models\RiderProfile;
use App\Models\User;
use App\Mail\ManualPaymentNotification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class ManualPaymentController extends Controller
{
    /**
     * Customer notifies admin that payment was made via manual bank transfer.
     */
    public function notify(Request $request)
    {
        $validated = $request->validate([
            'escrow_transaction_id' => ['sometimes', 'nullable', 'integer', 'exists:escrow_transactions,id'],
            'job_id' => ['sometimes', 'nullable', 'integer', 'exists:jobs,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['sometimes', 'string', 'max:1000'],
            'payment_proof' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $user = $request->user();
        $transaction = null;

        // Prevent duplicate manual payment notifications for the same job 
        // unless the existing transactions for that job are 'paid' or 'cancelled'.
        if (!empty($validated['job_id'])) {
            $existing = EscrowTransaction::where('job_id', $validated['job_id'])
                ->whereNotIn('status', ['failed', 'cancelled']) // Block if status is anything other than paid or cancelled
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'A payment notification for this job is already active or awaiting admin review.',
                    'data' => $existing
                ], 409);
            }
        }
        // If an escrow_transaction_id was provided, ensure it isn't already active (block unless paid or cancelled)
        if (!empty($validated['escrow_transaction_id'])) {
            $et = EscrowTransaction::find($validated['escrow_transaction_id']);

            if ($et && !in_array($et->status, ['failed', 'cancelled']) && $et->manual_payment_notified) {
                return response()->json([
                    'success' => false,
                    'message' => 'This payment notification is already being processed. You cannot update it unless its status is paid or cancelled.',
                    'data' => $et
                ], 409);
            }
        }

        if (!empty($validated['escrow_transaction_id'])) {
            $transaction = EscrowTransaction::with('user')->find($validated['escrow_transaction_id']);
        }

        if (!$transaction && $user) {
            $job = null;
            $platformFee = 0;
            $riderPayout = 0;
            $riderProfileId = 0;

            if (!empty($validated['job_id'])) {
                $job = Job::find($validated['job_id']);
            }

            if ($job) {
                $platformFee = $job->platform_charge ?? $job->order_fee ?? 0;
                if ($platformFee < 0) {
                    $platformFee = 0;
                }

                $riderPayout = max(0, round($validated['amount'] - $platformFee, 2));

                $riderApplication = $job->riderApplication ?? $job->acceptedApplication;
                if ($riderApplication) {
                    $riderProfileId = RiderProfile::where('user_id', $riderApplication->user_rider_id)
                        ->value('id');
                }
            }

            $createData = [
                'user_id' => $user->id,
                'job_id' => $validated['job_id'] ?? null,
                'rider_profile_id' => $riderProfileId ?: null,
                'balance' => $validated['amount'],
                'platform_fee' => $platformFee,
                'rider_payout' => $riderPayout,
                'status' => EscrowTransaction::STATUS_PENDING,
                'payment_reference' => $validated['payment_reference'] ?? null,
                'payment_method' => 'bank_transfer',
                'manual_payment_notified' => true,
            ];

            if ($request->hasFile('payment_proof')) {
                $createData['payment_proof_path'] = $request->file('payment_proof')->store('manual-payment-proofs', 'public');
            }

            $transaction = EscrowTransaction::create($createData);
        }

        if ($transaction) {
            $updateData = [
                'job_id' => $validated['job_id'] ?? $transaction->job_id,
                'payment_method' => 'bank_transfer',
                'manual_payment_notified' => true,
            ];

            if ($request->hasFile('payment_proof')) {
                $path = $request->file('payment_proof')->store('manual-payment-proofs', 'public');
                $updateData['payment_proof_path'] = $path;
            }

            $transaction->update($updateData);
            $transaction->load('user');
        }

        // Build list of admin emails. Some installations may not have `user_level_id` column.
        $adminQuery = User::query()->where('user_type', 'admin');

        if (Schema::hasColumn('users', 'user_level_id')) {
            $adminQuery->orWhere('user_level_id', 7);
        }

        $adminEmails = $adminQuery->pluck('email')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($adminEmails)) {
            $defaultAdminEmail = env('ADMIN_EMAIL', null);
            if (!empty($defaultAdminEmail)) {
                $adminEmails = array_map('trim', explode(',', $defaultAdminEmail));
            }
        }

        if (!empty($adminEmails) && $transaction) {
            Mail::to($adminEmails)->send(new ManualPaymentNotification($transaction));
        }

        return response()->json([
            'success' => true,
            'message' => 'Admin notified. Awaiting approval.',
            'data' => $transaction ? $transaction->fresh() : null,
        ]);
    }
}
