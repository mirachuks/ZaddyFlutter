<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('escrow_transactions', function (Blueprint $table) {
            // Add field to track when rider funds should be released
            $table->timestamp('rider_release_scheduled_at')->nullable()->after('status');

            // Track if funds have been released to rider
            $table->boolean('rider_funds_released')->default(false)->after('rider_release_scheduled_at');

            // Track if order was cancelled and refund issued
            $table->boolean('refund_issued')->default(false)->after('rider_funds_released');
            $table->timestamp('refund_issued_at')->nullable()->after('refund_issued');
        });
    }

    public function down(): void
    {
        Schema::table('escrow_transactions', function (Blueprint $table) {
            $table->dropColumn(['rider_release_scheduled_at', 'rider_funds_released', 'refund_issued', 'refund_issued_at']);
        });
    }
};
