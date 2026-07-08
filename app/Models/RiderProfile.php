<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiderProfile extends Model
{

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'legal_name',
        'email',
        'mobile_number',
        'service_zone',
        'nin',
        'gender',
        'state',
        'current_latitude',
        'current_longitude',
        'review_rank',
        'mobility_type',
        'mobility_brand',
        'mobility_model',
        'production_year',
        'current_lat',
        'current_lng',
        'plate_number',
        'image',
        'status',
        'total_trips',
        'is_available',
        'license_number',
        'license_expiry_date',
        'license_image',
        'license_back_image',
        'bike_brand',
        'bike_model',
        'bike_production_year',
        'bike_plate_number',
        'bike_color',
        'bike_registration_cert',
        'bike_image',
        'bike_engine_number',
        'bike_chassis_number',
        'guarantors',
        'bank_account_name',
        'bank_account_number',
        'bank_name',
        'bank_code',
    ];

    protected $casts = [
        'guarantors' => 'array',
    ];

    /**
     * A rider profile belongs to a user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Guarantor details are stored as JSON in the rider_profiles table.
     */
}
