<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{

    use HasFactory;

    protected $fillable = [
        'user_id',
        'rider_profile_id',
        'job_id',
        'review',
        'score',
    ];

    protected $casts = [
        'score'           => 'integer',
        'user_id'         => 'integer',
        'rider_profile_id' => 'integer',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function riderProfile(): BelongsTo
    {
        return $this->belongsTo(RiderProfile::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeForRider($query, int $riderProfileId)
    {
        return $query->where('rider_profile_id', $riderProfileId);
    }

    public function scopeByScore($query, int $min, int $max = 5)
    {
        return $query->whereBetween('score', [$min, $max]);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
