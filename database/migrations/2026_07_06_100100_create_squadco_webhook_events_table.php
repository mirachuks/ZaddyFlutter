<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('squadco_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type')->nullable();
            $table->string('reference')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('squadco_webhook_events');
    }
};
