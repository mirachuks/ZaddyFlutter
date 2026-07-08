<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            if (! Schema::hasColumn('withdrawals', 'reference')) {
                $table->string('reference')->nullable()->unique()->after('id');
            }
            if (! Schema::hasColumn('withdrawals', 'provider_response')) {
                $table->json('provider_response')->nullable()->after('reference');
            }
        });
    }

    public function down()
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            if (Schema::hasColumn('withdrawals', 'provider_response')) {
                $table->dropColumn('provider_response');
            }
            if (Schema::hasColumn('withdrawals', 'reference')) {
                $table->dropColumn('reference');
            }
        });
    }
};
