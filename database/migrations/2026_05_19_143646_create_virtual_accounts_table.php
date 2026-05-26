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
        Schema::create('virtual_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('email')->nullable();
            $table->string('account_number');
            $table->string('txt_ref')->unique();
            $table->string('order_ref')->unique();
            $table->string('bank_name');
            $table->string('user_id');
            $table->string('status')->nullable();
            $table->string('bvn')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('virtual_accounts');
    }
};
