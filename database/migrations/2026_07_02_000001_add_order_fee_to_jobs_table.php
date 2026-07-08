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
        Schema::table('jobs', function (Blueprint $table) {
            if (! Schema::hasColumn('jobs', 'order_fee')) {
                $table->decimal('order_fee', 10, 2)->nullable()->after('platform_charge');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            if (Schema::hasColumn('jobs', 'order_fee')) {
                $table->dropColumn('order_fee');
            }
        });
    }
};
