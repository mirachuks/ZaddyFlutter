<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfileUpdateRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'old_data',
        'new_data',
        'status',
        'admin_note',
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
    ];

    // Relationship to User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scope to get pending requests
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // Scope to get approved requests
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    // Scope to get rejected requests
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}
