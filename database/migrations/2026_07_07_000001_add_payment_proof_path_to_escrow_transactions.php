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
            if (!Schema::hasColumn('escrow_transactions', 'payment_proof_path')) {
                $table->string('payment_proof_path')->nullable()->after('payment_method');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('escrow_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('escrow_transactions', 'payment_proof_path')) {
                $table->dropColumn('payment_proof_path');
            }
        });
    }
};
