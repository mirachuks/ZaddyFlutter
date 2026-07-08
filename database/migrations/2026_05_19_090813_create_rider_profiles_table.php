<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rider_profiles', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('legal_name')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile_number')->nullable();
            $table->string('service_zone')->nullable();
            $table->string('nin')->nullable();
            $table->string('gender')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('avatar')->nullable();
            $table->string('state')->nullable();
            $table->string('current_latitude')->nullable();
            $table->string('current_longitude')->nullable();
            $table->string('review_rank')->nullable();
            $table->string('is_available')->nullable();
            $table->string('mobility_type')->default('bike'); // bike, van
            $table->string('mobility_brand')->nullable(); // honda, baja, carter
            $table->string('mobility_model')->nullable(); //carter 100
            $table->string('production_year')->nullable();
            $table->string('total_trips')->default('0');
            $table->string('current_lat')->nullable();
            $table->string('current_lng')->nullable();
            $table->string('plate_number')->nullable();
            $table->string('status')->default('inactive'); //active, suspended, banned
            $table->string('image')->nullable();
            $table->string('license_number')->nullable();
            $table->date('license_expiry_date')->nullable();
            $table->string('license_image')->nullable();
            $table->string('license_back_image')->nullable();
            $table->string('bike_brand')->nullable();
            $table->string('bike_model')->nullable();
            $table->string('bike_production_year')->nullable();
            $table->string('bike_plate_number')->nullable();
            $table->string('bike_color')->nullable();
            $table->string('bike_registration_cert')->nullable();
            $table->string('bike_image')->nullable();
            $table->string('bike_engine_number')->nullable();
            $table->string('bike_chassis_number')->nullable();
            $table->json('guarantors')->nullable();
            $table->timestamps();
        });
    }

    /** 
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rider_profiles');
    }
};
