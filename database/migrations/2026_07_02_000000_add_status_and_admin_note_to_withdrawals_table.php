<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            if (! Schema::hasColumn('withdrawals', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
            }

            if (! Schema::hasColumn('withdrawals', 'amount')) {
                $table->decimal('amount', 12, 2)->default(0)->after('user_id');
            }

            if (! Schema::hasColumn('withdrawals', 'status')) {
                $table->string('status')->default('pending')->after('amount');
            }

            if (! Schema::hasColumn('withdrawals', 'admin_note')) {
                $table->text('admin_note')->nullable()->after('status');
            }
        });

        if (Schema::hasTable('withdrawals') && Schema::hasColumn('withdrawals', 'user_id')) {
            // Only add the foreign key if it doesn't already exist. Some environments
            // may have created the `withdrawals` table with the FK already.
            $dbName = DB::getDatabaseName();
            $existing = DB::select(
                'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
                [$dbName, 'withdrawals', 'user_id']
            );

            if (empty($existing)) {
                Schema::table('withdrawals', function (Blueprint $table) {
                    $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            if (Schema::hasColumn('withdrawals', 'admin_note')) {
                $table->dropColumn('admin_note');
            }

            if (Schema::hasColumn('withdrawals', 'status')) {
                $table->dropColumn('status');
            }

            if (Schema::hasColumn('withdrawals', 'amount')) {
                $table->dropColumn('amount');
            }

            if (Schema::hasColumn('withdrawals', 'user_id')) {
                $table->dropColumn('user_id');
            }
        });
    }
};
