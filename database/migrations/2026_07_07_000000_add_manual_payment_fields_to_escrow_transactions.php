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
        Schema::table('escrow_transactions', function (Blueprint $table) {
            $table->string('payment_reference')->nullable()->after('refunded_at');
            $table->string('payment_method')->nullable()->default('bank_transfer')->after('payment_reference');
            $table->boolean('manual_payment_notified')->default(false)->after('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('escrow_transactions', function (Blueprint $table) {
            $table->dropColumn(['payment_reference', 'payment_method', 'manual_payment_notified']);
        });
    }
};
