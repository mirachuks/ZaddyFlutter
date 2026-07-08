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
            if (!Schema::hasColumn('jobs', 'title')) {
                $table->string('title')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('jobs', 'description')) {
                $table->text('description')->nullable()->after('title');
            }
            if (!Schema::hasColumn('jobs', 'pickup_address')) {
                $table->string('pickup_address')->nullable()->after('description');
            }
            if (!Schema::hasColumn('jobs', 'pickup_lat')) {
                $table->decimal('pickup_lat', 10, 7)->nullable()->after('pickup_address');
            }
            if (!Schema::hasColumn('jobs', 'pickup_lng')) {
                $table->decimal('pickup_lng', 10, 7)->nullable()->after('pickup_lat');
            }
            if (!Schema::hasColumn('jobs', 'dropoff_address')) {
                $table->string('dropoff_address')->nullable()->after('pickup_lng');
            }
            if (!Schema::hasColumn('jobs', 'dropoff_lat')) {
                $table->decimal('dropoff_lat', 10, 7)->nullable()->after('dropoff_address');
            }
            if (!Schema::hasColumn('jobs', 'dropoff_lng')) {
                $table->decimal('dropoff_lng', 10, 7)->nullable()->after('dropoff_lat');
            }
            if (!Schema::hasColumn('jobs', 'mobility_type_needed')) {
                $table->string('mobility_type_needed')->nullable()->after('dropoff_lng');
            }
            if (!Schema::hasColumn('jobs', 'price')) {
                $table->decimal('price', 10, 2)->nullable()->after('mobility_type_needed');
            }
            if (!Schema::hasColumn('jobs', 'price_type')) {
                $table->string('price_type')->default('fixed')->after('price');
            }
            if (!Schema::hasColumn('jobs', 'status')) {
                $table->string('status')->default('open')->after('price_type');
            }
            if (!Schema::hasColumn('jobs', 'posted_at')) {
                $table->timestamp('posted_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('jobs', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('posted_at');
            }
            if (!Schema::hasColumn('jobs', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable()->after('expires_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn([
                'title',
                'description',
                'pickup_address',
                'pickup_lat',
                'pickup_lng',
                'dropoff_address',
                'dropoff_lat',
                'dropoff_lng',
                'mobility_type_needed',
                'price',
                'price_type',
                'status',
                'posted_at',
                'expires_at',
                'delivered_at',
            ]);
        });
    }
};
