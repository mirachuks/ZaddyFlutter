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
        // Make rider_profile_id nullable to allow customer top-ups
        // Note: This uses the `change()` method which requires doctrine/dbal to be installed.
        Schema::table('escrow_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('escrow_transactions', 'rider_profile_id')) {
                $table->bigInteger('rider_profile_id')->unsigned()->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('escrow_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('escrow_transactions', 'rider_profile_id')) {
                $table->bigInteger('rider_profile_id')->unsigned()->nullable(false)->change();
            }
        });
    }
};
