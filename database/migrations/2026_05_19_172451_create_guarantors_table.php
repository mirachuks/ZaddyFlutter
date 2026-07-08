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
        Schema::create('guarantors', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('rider_profile_id')->unsigned();
            $table->string('name');
            $table->string('state')->nullable();
            $table->string('address')->nullable();
            $table->string('mobile_no');
            $table->string('nin');
            $table->string('email')->nullable();
            $table->string('id_type')->nullable();
            $table->string('relationship')->nullable();
            $table->string('nin_image')->nullable();
            $table->string('id_image')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guarantors');
    }
};
