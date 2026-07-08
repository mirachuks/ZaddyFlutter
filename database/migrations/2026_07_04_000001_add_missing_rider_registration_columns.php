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
        Schema::table('rider_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('rider_profiles', 'license_number')) {
                $table->string('license_number')->nullable();
            }
            if (!Schema::hasColumn('rider_profiles', 'license_expiry_date')) {
                $table->date('license_expiry_date')->nullable();
            }
            if (!Schema::hasColumn('rider_profiles', 'license_image')) {
                $table->string('license_image')->nullable();
            }
            if (!Schema::hasColumn('rider_profiles', 'license_back_image')) {
                $table->string('license_back_image')->nullable();
            }
            if (!Schema::hasColumn('rider_profiles', 'bike_brand')) {
                $table->string('bike_brand')->nullable();
            }
            if (!Schema::hasColumn('rider_profiles', 'bike_model')) {
                $table->string('bike_model')->nullable();
            }
            if (!Schema::hasColumn('rider_profiles', 'bike_production_year')) {
                $table->string('bike_production_year')->nullable();
            }
            if (!Schema::hasColumn('rider_profiles', 'bike_plate_number')) {
                $table->string('bike_plate_number')->nullable();
            }
            if (!Schema::hasColumn('rider_profiles', 'bike_color')) {
                $table->string('bike_color')->nullable();
            }
            if (!Schema::hasColumn('rider_profiles', 'bike_registration_cert')) {
                $table->string('bike_registration_cert')->nullable();
            }
            if (!Schema::hasColumn('rider_profiles', 'bike_image')) {
                $table->string('bike_image')->nullable();
            }
            if (!Schema::hasColumn('rider_profiles', 'bike_engine_number')) {
                $table->string('bike_engine_number')->nullable();
            }
            if (!Schema::hasColumn('rider_profiles', 'bike_chassis_number')) {
                $table->string('bike_chassis_number')->nullable();
            }
        });

        Schema::table('guarantors', function (Blueprint $table) {
            if (!Schema::hasColumn('guarantors', 'email')) {
                $table->string('email')->nullable();
            }
            if (!Schema::hasColumn('guarantors', 'id_type')) {
                $table->string('id_type')->nullable();
            }
            if (!Schema::hasColumn('guarantors', 'relationship')) {
                $table->string('relationship')->nullable();
            }
            if (!Schema::hasColumn('guarantors', 'nin_image')) {
                $table->string('nin_image')->nullable();
            }
            if (!Schema::hasColumn('guarantors', 'id_image')) {
                $table->string('id_image')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rider_profiles', function (Blueprint $table) {
            foreach (['license_number', 'license_expiry_date', 'license_image', 'license_back_image', 'bike_brand', 'bike_model', 'bike_production_year', 'bike_plate_number', 'bike_color', 'bike_registration_cert', 'bike_image', 'bike_engine_number', 'bike_chassis_number'] as $column) {
                if (Schema::hasColumn('rider_profiles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('guarantors', function (Blueprint $table) {
            foreach (['email', 'id_type', 'relationship', 'nin_image', 'id_image'] as $column) {
                if (Schema::hasColumn('guarantors', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
