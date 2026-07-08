<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dispute extends Model
{
    protected $fillable = ['user_id', 'title', 'description', 'status', 'resolution_note'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
