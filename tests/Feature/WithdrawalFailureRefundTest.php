<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserWallet;
use App\Models\Withdrawal;
use App\Services\SquadcoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WithdrawalFailureRefundTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_payout_refunds_wallet_and_marks_failed()
    {
        $user = User::create([
            'first_name' => 'Fail',
            'last_name' => 'Case',
            'email' => 'fail.case@example.com',
            'password' => bcrypt('password'),
            'mobile_number' => '0800000001',
            'user_type' => 'rider',
            'status' => 'active',
            'is_verified' => 'yes',
        ]);

        UserWallet::create(['user_id' => $user->id, 'wallet_no' => 'WALLETFAIL', 'balance' => 3000]);

        $withdrawal = Withdrawal::create(['user_id' => $user->id, 'amount' => 2000, 'status' => 'pending']);

        // create rider profile with bank details so controller attempts payout
        \App\Models\RiderProfile::create([
            'user_id' => $user->id,
            'first_name' => 'Fail',
            'last_name' => 'Case',
            'legal_name' => 'Fail Case',
            'email' => $user->email,
            'mobile_number' => $user->mobile_number,
            'status' => 'active',
            'is_available' => 'no',
            'bank_account_number' => '0001112223',
            'bank_code' => '001',
        ]);

        // mock SquadcoService to throw an exception simulating provider failure
        $this->app->instance(SquadcoService::class, new class {
            public function payoutToBank($payload)
            {
                throw new \Exception('provider error');
            }
            public function createVirtualAccount($payload)
            {
                return [];
            }
        });

        // call admin approve controller directly
        $controller = new \App\Http\Controllers\Withdrawal\AdminWithdrawalController();
        $req = new \Illuminate\Http\Request();
        $resp = $controller->approve($req, $withdrawal);
        $this->assertEquals(200, $resp->getStatusCode());

        $this->assertDatabaseHas('withdrawals', ['id' => $withdrawal->id, 'status' => 'failed']);
        $this->assertDatabaseHas('user_wallets', ['user_id' => $user->id, 'balance' => 3000]);
    }
}
