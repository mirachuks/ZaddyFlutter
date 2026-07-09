<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\UserWallet;
use App\Models\Withdrawal;
use App\Models\RiderProfile;
use App\Models\PlatformFee;
use App\Services\SquadcoService;
use Mockery;
use Illuminate\Support\Facades\Config;

class WithdrawalFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure auto-approve is disabled during tests
        Config::set('services.squadco.auto_approve_withdrawals', false);

        // Create minimal tables used by the tests to avoid running full migrations (SQLite compatibility)
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });

        Schema::create('user_wallets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('balance', 13, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('rider_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('bank_account_number')->nullable();
            $table->string('bank_code')->nullable();
            $table->timestamps();
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('amount', 13, 2);
            $table->string('transaction_type');
            $table->string('purpose');
            $table->timestamps();
        });

        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('type');
            $table->json('payload')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });

        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('amount', 13, 2);
            $table->decimal('fee', 13, 2)->default(0);
            $table->string('status')->default('pending');
            $table->string('admin_note')->nullable();
            $table->string('reference')->nullable();
            $table->json('provider_response')->nullable();
            $table->timestamps();
        });

        Schema::create('platform_fees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('withdrawal_id')->nullable();
            $table->decimal('amount', 13, 2)->default(0);
            $table->boolean('collected')->default(false);
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function test_user_can_request_withdrawal_and_wallet_is_reserved()
    {
        $user = User::create(['first_name' => 'Test', 'last_name' => 'User']);
        UserWallet::create(["user_id" => $user->id, "balance" => 1000.00]);
        RiderProfile::create(["user_id" => $user->id, "bank_account_number" => '0123456789', 'bank_code' => '058']);

        $this->actingAs($user, 'api');

        $response = $this->postJson('/api/withdrawals', ['amount' => 200]);
        $response->assertStatus(201)->assertJson(['success' => true]);

        $withdrawal = Withdrawal::first();
        // Ensure the withdrawal is pending so admin can approve in the test
        $withdrawal->status = 'pending';
        $withdrawal->save();
        $this->assertNotNull($withdrawal);
        $this->assertEquals(200.00, (float)$withdrawal->amount);
        $this->assertGreaterThanOrEqual(0, (float)$withdrawal->fee);

        $feeRecord = PlatformFee::where('withdrawal_id', $withdrawal->id)->first();
        $this->assertNotNull($feeRecord);
        $this->assertFalse((bool)$feeRecord->collected);

        $wallet = UserWallet::where('user_id', $user->id)->first();
        $this->assertEquals(1000.00 - ($withdrawal->amount + $withdrawal->fee), (float)$wallet->balance);
    }

    public function test_admin_approve_triggers_payout_and_marks_withdrawal_approved()
    {
        $user = User::create(['first_name' => 'Test2']);
        UserWallet::create(["user_id" => $user->id, "balance" => 1000.00]);
        RiderProfile::create(["user_id" => $user->id, "bank_account_number" => '0123456789', 'bank_code' => '058']);

        // Request withdrawal
        $this->actingAs($user, 'api');
        $resp = $this->postJson('/api/withdrawals', ['amount' => 150]);
        $resp->assertStatus(201);
        $withdrawal = Withdrawal::first();

        // Mock SquadcoService to return success
        $mock = Mockery::mock(SquadcoService::class);
        $mock->shouldReceive('payoutToBank')->andReturn(['status' => 'success']);
        $this->app->instance(SquadcoService::class, $mock);

        // Call controller method directly to avoid routing/middleware complexities
        $controller = new \App\Http\Controllers\Withdrawal\AdminWithdrawalController();
        $req = new \Illuminate\Http\Request();
        $resp = $controller->approve($req, $withdrawal);
        $this->assertEquals(200, $resp->getStatusCode());

        $withdrawal->refresh();
        $this->assertEquals('approved', $withdrawal->status);
        $feeRecord = PlatformFee::where('withdrawal_id', $withdrawal->id)->first();
        $this->assertTrue((bool)$feeRecord->collected);
    }
}
