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
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('job_id')->unsigned();
            $table->bigInteger('user_rider_id')->unsigned();
            $table->text('msg')->nullable();
            $table->string('status'); //pending, accepted, rejected, withrawn 
            $table->string('bid_price')->nullable();// if negotiable 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
