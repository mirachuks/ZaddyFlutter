<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rider_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('rider_profiles', 'bank_account_name')) {
                $table->string('bank_account_name')->nullable();
            }
            if (!Schema::hasColumn('rider_profiles', 'bank_account_number')) {
                $table->string('bank_account_number')->nullable();
            }
            if (!Schema::hasColumn('rider_profiles', 'bank_name')) {
                $table->string('bank_name')->nullable();
            }
            if (!Schema::hasColumn('rider_profiles', 'bank_code')) {
                $table->string('bank_code')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('rider_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('rider_profiles', 'bank_account_name')) {
                $table->dropColumn('bank_account_name');
            }
            if (Schema::hasColumn('rider_profiles', 'bank_account_number')) {
                $table->dropColumn('bank_account_number');
            }
            if (Schema::hasColumn('rider_profiles', 'bank_name')) {
                $table->dropColumn('bank_name');
            }
            if (Schema::hasColumn('rider_profiles', 'bank_code')) {
                $table->dropColumn('bank_code');
            }
        });
    }
};
