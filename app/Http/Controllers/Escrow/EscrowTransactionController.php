<?php

namespace App\Http\Controllers\Escrow;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EscrowTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EscrowTransactionController extends Controller
{
     // -------------------------------------------------------------------------
    // GET /escrow-transactions
    // List all escrow transactions (filterable by status, user, rider)
    // -------------------------------------------------------------------------
    public function index(Request $request): JsonResponse
    {
        $query = EscrowTransaction::with(['user', 'riderProfile'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->user_id, fn($q) => $q->where('user_id', $request->user_id))
            ->when($request->rider_profile_id, fn($q) => $q->where('rider_profile_id', $request->rider_profile_id))
            ->when($request->release_trigger, fn($q) => $q->where('release_trigger', $request->release_trigger))
            ->latest();
 
        $transactions = $query->paginate($request->per_page ?? 15);
 
        return response()->json([
            'success' => true,
            'data'    => $transactions,
        ]);
    }
 
    // -------------------------------------------------------------------------
    // POST /escrow-transactions
    // Initiate a new escrow transaction (status = pending)
    // -------------------------------------------------------------------------
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id'            => ['required', 'integer', 'exists:users,id'],
            'rider_profile_id'   => ['required', 'integer', 'exists:rider_profiles,id'],
            'balance'            => ['required', 'numeric', 'min:0.01'],
            'platform_fee'       => ['required', 'numeric', 'min:0'],
            'rider_payout'       => ['required', 'numeric', 'min:0'],
            'release_trigger'    => ['sometimes', Rule::in(['otp', 'auto', 'manual'])],
            'auto_release_hours' => ['sometimes', 'integer', 'min:1', 'max:168'], // max 7 days
        ]);
 
        // Sanity check: platform_fee + rider_payout should equal balance
        $sum = round($validated['platform_fee'] + $validated['rider_payout'], 2);
        if ($sum !== round($validated['balance'], 2)) {
            return response()->json([
                'success' => false,
                'message' => 'platform_fee + rider_payout must equal balance.',
            ], 422);
        }
 
        $transaction = EscrowTransaction::create([
            ...$validated,
            'status' => EscrowTransaction::STATUS_PENDING,
        ]);
 
        return response()->json([
            'success' => true,
            'message' => 'Escrow transaction created.',
            'data'    => $transaction,
        ], 201);
    }
 
    // -------------------------------------------------------------------------
    // GET /escrow-transactions/{id}
    // Show a single escrow transaction
    // -------------------------------------------------------------------------
    public function show(EscrowTransaction $escrowTransaction): JsonResponse
    {
        $escrowTransaction->load(['user', 'riderProfile']);
 
        return response()->json([
            'success' => true,
            'data'    => $escrowTransaction,
        ]);
    }
 
    // -------------------------------------------------------------------------
    // PUT /escrow-transactions/{id}
    // Update mutable fields (only when status is still 'pending')
    // -------------------------------------------------------------------------
    public function update(Request $request, EscrowTransaction $escrowTransaction): JsonResponse
    {
        if ($escrowTransaction->status !== EscrowTransaction::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending transactions can be updated.',
            ], 422);
        }
 
        $validated = $request->validate([
            'balance'            => ['sometimes', 'numeric', 'min:0.01'],
            'platform_fee'       => ['sometimes', 'numeric', 'min:0'],
            'rider_payout'       => ['sometimes', 'numeric', 'min:0'],
            'release_trigger'    => ['sometimes', Rule::in(['otp', 'auto', 'manual'])],
            'auto_release_hours' => ['sometimes', 'integer', 'min:1', 'max:168'],
        ]);
 
        $escrowTransaction->update($validated);
 
        return response()->json([
            'success' => true,
            'message' => 'Escrow transaction updated.',
            'data'    => $escrowTransaction->fresh(),
        ]);
    }
 
    // -------------------------------------------------------------------------
    // DELETE /escrow-transactions/{id}
    // Soft-cancel: only allowed on 'pending' transactions
    // -------------------------------------------------------------------------
    public function destroy(EscrowTransaction $escrowTransaction): JsonResponse
    {
        if ($escrowTransaction->status !== EscrowTransaction::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending transactions can be deleted.',
            ], 422);
        }
 
        $escrowTransaction->delete();
 
        return response()->json([
            'success' => true,
            'message' => 'Escrow transaction deleted.',
        ]);
    }
 
    // =========================================================================
    // STATUS TRANSITION ACTIONS
    // =========================================================================
 
    // -------------------------------------------------------------------------
    // POST /escrow-transactions/{id}/hold
    // Mark funds as held (pending → held). Called after payment confirmation.
    // -------------------------------------------------------------------------
    public function hold(EscrowTransaction $escrowTransaction): JsonResponse
    {
        if ($escrowTransaction->status !== EscrowTransaction::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending transactions can be held.',
            ], 422);
        }
 
        $escrowTransaction->update([
            'status'  => EscrowTransaction::STATUS_HELD,
            'paid_at' => now(),
        ]);
 
        return response()->json([
            'success' => true,
            'message' => 'Funds are now held in escrow.',
            'data'    => $escrowTransaction->fresh(),
        ]);
    }
 
    // -------------------------------------------------------------------------
    // POST /escrow-transactions/{id}/release
    // Release funds to rider (held → released).
    // Supports otp, auto, and manual triggers.
    // -------------------------------------------------------------------------
    public function release(Request $request, EscrowTransaction $escrowTransaction): JsonResponse
    {
        if (! $escrowTransaction->isReleasable()) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction is not in a held state and cannot be released.',
            ], 422);
        }
 
        $trigger = $escrowTransaction->release_trigger;
 
        // OTP-triggered release: validate OTP before releasing
        if ($trigger === EscrowTransaction::TRIGGER_OTP) {
            $request->validate([
                'otp' => ['required', 'string'],
            ]);
 
            // TODO: Replace with your actual OTP verification service
            $otpValid = $this->verifyOtp($escrowTransaction, $request->otp);
 
            if (! $otpValid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid OTP. Release denied.',
                ], 403);
            }
        }
 
        // Auto-triggered release: check time window
        if ($trigger === EscrowTransaction::TRIGGER_AUTO) {
            if (! $escrowTransaction->isAutoReleaseElapsed()) {
                $hoursLeft = $escrowTransaction->auto_release_hours
                    - now()->diffInHours($escrowTransaction->paid_at);
 
                return response()->json([
                    'success' => false,
                    'message' => "Auto-release window has not elapsed. {$hoursLeft} hour(s) remaining.",
                ], 422);
            }
        }
 
        // Manual trigger: no additional checks — caller is trusted (e.g. admin)
 
        DB::transaction(function () use ($escrowTransaction) {
            $escrowTransaction->update([
                'status' => EscrowTransaction::STATUS_RELEASED,
            ]);
 
            // TODO: Trigger actual payout to rider here
            // PayoutService::send($escrowTransaction->riderProfile, $escrowTransaction->rider_payout);
        });
 
        return response()->json([
            'success' => true,
            'message' => 'Funds released to rider.',
            'data'    => $escrowTransaction->fresh(),
        ]);
    }
 
    // -------------------------------------------------------------------------
    // POST /escrow-transactions/{id}/refund
    // Refund funds to user (held → refunded).
    // -------------------------------------------------------------------------
    public function refund(Request $request, EscrowTransaction $escrowTransaction): JsonResponse
    {
        if (! $escrowTransaction->isReleasable()) {
            return response()->json([
                'success' => false,
                'message' => 'Only held transactions can be refunded.',
            ], 422);
        }
 
        $request->validate([
            'reason' => ['sometimes', 'string', 'max:500'],
        ]);
 
        DB::transaction(function () use ($escrowTransaction) {
            $escrowTransaction->update([
                'status'      => EscrowTransaction::STATUS_REFUNDED,
                'refunded_at' => now(),
            ]);
 
            // TODO: Trigger actual refund to user here
            // RefundService::send($escrowTransaction->user, $escrowTransaction->balance);
        });
 
        return response()->json([
            'success' => true,
            'message' => 'Transaction refunded to user.',
            'data'    => $escrowTransaction->fresh(),
        ]);
    }
 
    // -------------------------------------------------------------------------
    // POST /escrow-transactions/{id}/dispute
    // Flag a held transaction as disputed.
    // -------------------------------------------------------------------------
    public function dispute(Request $request, EscrowTransaction $escrowTransaction): JsonResponse
    {
        if (! $escrowTransaction->isReleasable()) {
            return response()->json([
                'success' => false,
                'message' => 'Only held transactions can be disputed.',
            ], 422);
        }
 
        $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);
 
        $escrowTransaction->update([
            'status' => EscrowTransaction::STATUS_DISPUTED,
        ]);
 
        // TODO: Notify admin / open dispute ticket
        // DisputeService::open($escrowTransaction, $request->reason);
 
        return response()->json([
            'success' => true,
            'message' => 'Transaction marked as disputed. Our team will review.',
            'data'    => $escrowTransaction->fresh(),
        ]);
    }
 
    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================
 
    /**
     * Stub OTP verification — replace with your real implementation.
     */
    private function verifyOtp(EscrowTransaction $transaction, string $otp): bool
    {
        // Example: cache-based OTP check
        // return Cache::get("escrow_otp_{$transaction->id}") === $otp;
        return true; // Replace this stub
    }
    
}
