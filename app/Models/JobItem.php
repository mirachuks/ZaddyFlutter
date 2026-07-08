<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobItem extends Model
{
    protected $fillable = [
        'job_id',
        'title',
        'receiver_name',
        'receiver_phone',
        'item_category',
        'description',
        'pickup_address',
        'pickup_lat',
        'pickup_lng',
        'dropoff_address',
        'dropoff_lat',
        'dropoff_lng',
        'mobility_type_needed',
        'price_type',
        'status',
        'posted_at',
        'expires_at',
        'delivered_at',
    ];

    protected $casts = [
        'posted_at'    => 'datetime',
        'expires_at'   => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }
}
