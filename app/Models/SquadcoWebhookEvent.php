<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SquadcoWebhookEvent extends Model
{
    protected $fillable = ['event_type', 'reference', 'payload'];

    protected $casts = [
        'payload' => 'array',
    ];
}
