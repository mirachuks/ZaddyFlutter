<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('escrow_transactions', function (Blueprint $table) {
            // Add job_id to link escrow transaction to job
            $table->bigInteger('job_id')->unsigned()->nullable()->after('user_id');
        });
    }

    public function down(): void
    {
        // Try to drop foreign key if it exists (for production databases)
        if (DB::connection()->getDriverName() === 'mysql') {
            try {
                Schema::table('escrow_transactions', function (Blueprint $table) {
                    if (Schema::hasColumn('escrow_transactions', 'job_id')) {
                        $table->dropForeign('escrow_transactions_job_id_foreign');
                    }
                });
            } catch (\Exception $e) {
                // Foreign key may not exist, continue
            }
        }

        Schema::table('escrow_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('escrow_transactions', 'job_id')) {
                $table->dropColumn('job_id');
            }
        });
    }
};
