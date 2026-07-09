<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_fees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('withdrawal_id')->nullable();
            $table->decimal('amount', 13, 2)->default(0);
            $table->boolean('collected')->default(false);
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_fees');
    }
};
