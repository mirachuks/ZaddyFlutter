<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'user_rider_id',
        'msg',
        'status',
        'bid_price',
    ];

    protected $casts = [
        'bid_price'     => 'decimal:2',
        'job_id'        => 'integer',
        'user_rider_id' => 'integer',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function userRider(): BelongsTo
    {
        // Points to the users table via user_rider_id
        return $this->belongsTo(User::class, 'user_rider_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeWithdrawn($query)
    {
        return $query->where('status', 'withdrawn');
    }

    public function scopeForJob($query, int $jobId)
    {
        return $query->where('job_id', $jobId);
    }

    public function scopeByRider($query, int $userId)
    {
        return $query->where('user_rider_id', $userId);
    }

}
