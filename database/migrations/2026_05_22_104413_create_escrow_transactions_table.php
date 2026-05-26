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
        Schema::create('escrow_transactions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('rider_profile_id')->unsigned();
            $table->double('balance', 15, 2);
            $table->double('platform_fee', 15, 2);
            $table->double('rider_payout', 15, 2);
            $table->string('status')->default('pending');// pending, held, released, refunded, disputed
            $table->string('release_trigger')->default('auto');// otp, auto, manual
            $table->timestamp('paid_at')->nullable(); 
            $table->unsignedInteger('auto_release_hours')->default(4);
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('escrow_transactions');
    }
};
