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
        Schema::table('reviews', function (Blueprint $table) {
            if (! Schema::hasColumn('reviews', 'job_id')) {
                $table->bigInteger('job_id')->unsigned()->nullable()->after('rider_profile_id');
                $table->foreign('job_id')->references('id')->on('jobs')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            if (Schema::hasColumn('reviews', 'job_id')) {
                $table->dropForeign(['job_id']);
                $table->dropColumn('job_id');
            }
        });
    }
};
