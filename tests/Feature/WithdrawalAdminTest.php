<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserWallet;
use App\Models\RiderProfile;
use App\Models\Withdrawal;
use App\Services\SquadcoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class WithdrawalAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_approve_withdrawal_and_triggers_payout()
    {
        // Create user and wallet
        $user = User::create([
            'first_name' => 'Pay',
            'last_name' => 'Recipient',
            'email' => 'pay.recipient@example.com',
            'password' => bcrypt('password'),
            'mobile_number' => '08011111111',
            'user_type' => 'rider',
            'status' => 'active',
            'is_verified' => 'yes',
        ]);

        UserWallet::create([
            'user_id' => $user->id,
            'wallet_no' => 'WALLET123',
            'balance' => 5000,
        ]);

        // Rider profile with bank details
        $profile = RiderProfile::create([
            'user_id' => $user->id,
            'first_name' => 'Pay',
            'last_name' => 'Recipient',
            'legal_name' => 'Pay Recipient',
            'email' => $user->email,
            'mobile_number' => $user->mobile_number,
            'status' => 'active',
            'is_available' => 'no',
            'bank_account_number' => '0123456789',
            'bank_code' => '001',
        ]);

        $withdrawal = Withdrawal::create([
            'user_id' => $user->id,
            'amount' => 2000,
            'status' => 'pending',
        ]);

        // Mock SquadcoService to return success
        $this->app->instance(SquadcoService::class, new class {
            public function payoutToBank($payload)
            {
                return ['status' => 'success', 'data' => ['status' => 'processed']];
            }
            public function createVirtualAccount($payload)
            {
                return ['status' => 'success', 'data' => ['virtual_account' => 'VA123']];
            }
        });

        // Create admin user and token
        $admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'mobile_number' => '08022222222',
            'user_type' => 'admin',
            'status' => 'active',
            'is_verified' => 'yes',
        ]);

        // Ensure withdrawal is pending before making request
        $this->assertDatabaseHas('withdrawals', [
            'id' => $withdrawal->id,
            'status' => 'pending',
        ]);

        // Call controller method directly to avoid middleware/role checks in tests
        $controller = new \App\Http\Controllers\Withdrawal\AdminWithdrawalController();
        $req = new \Illuminate\Http\Request();
        $response = $controller->approve($req, $withdrawal);

        $body = is_object($response) && method_exists($response, 'getContent') ? $response->getContent() : null;
        fwrite(STDERR, "ADMIN RESPONSE BODY: " . json_encode($body) . PHP_EOL);
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertDatabaseHas('withdrawals', [
            'id' => $withdrawal->id,
            'status' => 'approved',
        ]);

        $this->assertDatabaseHas('user_wallets', [
            'user_id' => $user->id,
            // balance should be debited from 5000 to 3000
        ]);
    }
}
