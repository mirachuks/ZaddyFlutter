<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('users', 'date_of_birth')) {
            Schema::table('users', function (Blueprint $table) {
                $table->date('date_of_birth')->nullable()->after('mobile_number');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('users', 'date_of_birth')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('date_of_birth');
            });
        }
    }
};
