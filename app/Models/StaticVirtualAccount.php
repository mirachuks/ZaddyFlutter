<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaticVirtualAccount extends Model
{
    protected $fillable = [
        'user_id',
        'account_name',
        'account_number',
        'bank_name',
        'customer_email',
        'customer_mobile',
        'txt_ref',
        'order_ref',
        'status',
        'message',
        'meta',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
