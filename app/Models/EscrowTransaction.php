<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class EscrowTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'job_id',
        'rider_profile_id',
        'balance',
        'platform_fee',
        'rider_payout',
        'status',
        'release_trigger',
        'paid_at',
        'auto_release_hours',
        'refunded_at',
        'payment_reference',
        'payment_method',
        'manual_payment_notified',
        'payment_proof_path',
        'rider_release_scheduled_at',
        'rider_funds_released',
        'refund_issued',
        'refund_issued_at',
    ];

    protected $casts = [
        'balance'            => 'float',
        'platform_fee'       => 'float',
        'rider_payout'       => 'float',
        'auto_release_hours' => 'integer',
        'job_id'             => 'integer',
        'paid_at'            => 'datetime',
        'refunded_at'        => 'datetime',
        'rider_release_scheduled_at' => 'datetime',
        'rider_funds_released' => 'boolean',
        'refund_issued'      => 'boolean',
        'refund_issued_at'   => 'datetime',
        'manual_payment_notified' => 'boolean',
    ];

    // Status constants
    const STATUS_PENDING  = 'pending';
    const STATUS_HELD     = 'held';
    const STATUS_RELEASED = 'released';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_DISPUTED = 'disputed';

    // Release trigger constants
    const TRIGGER_OTP    = 'otp';
    const TRIGGER_AUTO   = 'auto';
    const TRIGGER_MANUAL = 'manual';

    /**
     * Belongs to a user (payer/customer).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Belongs to a rider profile.
     */
    public function riderProfile()
    {
        return $this->belongsTo(RiderProfile::class);
    }

    /**
     * Check if funds can be released.
     */
    public function isReleasable(): bool
    {
        return $this->status === self::STATUS_HELD;
    }

    /**
     * Check if auto-release window has elapsed.
     */
    public function isAutoReleaseElapsed(): bool
    {
        if (! $this->paid_at) {
            return false;
        }

        return now()->diffInHours($this->paid_at) >= $this->auto_release_hours;
    }
}
