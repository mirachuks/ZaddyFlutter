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
            if (!Schema::hasColumn('rider_profiles', 'first_name')) {
                $table->string('first_name')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('rider_profiles', 'last_name')) {
                $table->string('last_name')->nullable()->after('first_name');
            }
            if (!Schema::hasColumn('rider_profiles', 'email')) {
                $table->string('email')->nullable()->after('last_name');
            }
            if (!Schema::hasColumn('rider_profiles', 'license_number')) {
                $table->string('license_number')->nullable()->after('image');
            }
            if (!Schema::hasColumn('rider_profiles', 'license_expiry_date')) {
                $table->date('license_expiry_date')->nullable()->after('license_number');
            }
            if (!Schema::hasColumn('rider_profiles', 'license_image')) {
                $table->string('license_image')->nullable()->after('license_expiry_date');
            }
            if (!Schema::hasColumn('rider_profiles', 'license_back_image')) {
                $table->string('license_back_image')->nullable()->after('license_image');
            }
            if (!Schema::hasColumn('rider_profiles', 'bike_brand')) {
                $table->string('bike_brand')->nullable()->after('license_back_image');
            }
            if (!Schema::hasColumn('rider_profiles', 'bike_model')) {
                $table->string('bike_model')->nullable()->after('bike_brand');
            }
            if (!Schema::hasColumn('rider_profiles', 'bike_production_year')) {
                $table->string('bike_production_year')->nullable()->after('bike_model');
            }
            if (!Schema::hasColumn('rider_profiles', 'bike_plate_number')) {
                $table->string('bike_plate_number')->nullable()->after('bike_production_year');
            }
            if (!Schema::hasColumn('rider_profiles', 'bike_color')) {
                $table->string('bike_color')->nullable()->after('bike_plate_number');
            }
            if (!Schema::hasColumn('rider_profiles', 'bike_registration_cert')) {
                $table->string('bike_registration_cert')->nullable()->after('bike_color');
            }
            if (!Schema::hasColumn('rider_profiles', 'bike_image')) {
                $table->string('bike_image')->nullable()->after('bike_registration_cert');
            }
            if (!Schema::hasColumn('rider_profiles', 'bike_engine_number')) {
                $table->string('bike_engine_number')->nullable()->after('bike_image');
            }
            if (!Schema::hasColumn('rider_profiles', 'bike_chassis_number')) {
                $table->string('bike_chassis_number')->nullable()->after('bike_engine_number');
            }
            if (!Schema::hasColumn('rider_profiles', 'guarantors')) {
                $table->json('guarantors')->nullable()->after('bike_registration_cert');
            }
        });

        Schema::table('guarantors', function (Blueprint $table) {
            if (!Schema::hasColumn('guarantors', 'email')) {
                $table->string('email')->nullable()->after('address');
            }
            if (!Schema::hasColumn('guarantors', 'id_type')) {
                $table->string('id_type')->nullable()->after('email');
            }
            if (!Schema::hasColumn('guarantors', 'relationship')) {
                $table->string('relationship')->nullable()->after('id_type');
            }
            if (!Schema::hasColumn('guarantors', 'nin_image')) {
                $table->string('nin_image')->nullable()->after('relationship');
            }
            if (!Schema::hasColumn('guarantors', 'id_image')) {
                $table->string('id_image')->nullable()->after('nin_image');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rider_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('rider_profiles', 'bike_chassis_number')) {
                $table->dropColumn('bike_chassis_number');
            }
            if (Schema::hasColumn('rider_profiles', 'bike_engine_number')) {
                $table->dropColumn('bike_engine_number');
            }
            if (Schema::hasColumn('rider_profiles', 'bike_image')) {
                $table->dropColumn('bike_image');
            }
            if (Schema::hasColumn('rider_profiles', 'bike_registration_cert')) {
                $table->dropColumn('bike_registration_cert');
            }
            if (Schema::hasColumn('rider_profiles', 'bike_color')) {
                $table->dropColumn('bike_color');
            }
            if (Schema::hasColumn('rider_profiles', 'bike_plate_number')) {
                $table->dropColumn('bike_plate_number');
            }
            if (Schema::hasColumn('rider_profiles', 'bike_production_year')) {
                $table->dropColumn('bike_production_year');
            }
            if (Schema::hasColumn('rider_profiles', 'bike_model')) {
                $table->dropColumn('bike_model');
            }
            if (Schema::hasColumn('rider_profiles', 'bike_brand')) {
                $table->dropColumn('bike_brand');
            }
            if (Schema::hasColumn('rider_profiles', 'license_back_image')) {
                $table->dropColumn('license_back_image');
            }
            if (Schema::hasColumn('rider_profiles', 'license_image')) {
                $table->dropColumn('license_image');
            }
            if (Schema::hasColumn('rider_profiles', 'license_expiry_date')) {
                $table->dropColumn('license_expiry_date');
            }
            if (Schema::hasColumn('rider_profiles', 'license_number')) {
                $table->dropColumn('license_number');
            }
        });

        Schema::table('guarantors', function (Blueprint $table) {
            if (Schema::hasColumn('guarantors', 'id_image')) {
                $table->dropColumn('id_image');
            }
            if (Schema::hasColumn('guarantors', 'nin_image')) {
                $table->dropColumn('nin_image');
            }
            if (Schema::hasColumn('guarantors', 'relationship')) {
                $table->dropColumn('relationship');
            }
            if (Schema::hasColumn('guarantors', 'id_type')) {
                $table->dropColumn('id_type');
            }
            if (Schema::hasColumn('guarantors', 'email')) {
                $table->dropColumn('email');
            }
        });

        Schema::table('rider_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('rider_profiles', 'guarantors')) {
                $table->dropColumn('guarantors');
            }
            if (Schema::hasColumn('rider_profiles', 'email')) {
                $table->dropColumn('email');
            }
            if (Schema::hasColumn('rider_profiles', 'last_name')) {
                $table->dropColumn('last_name');
            }
            if (Schema::hasColumn('rider_profiles', 'first_name')) {
                $table->dropColumn('first_name');
            }
        });
    }
};
