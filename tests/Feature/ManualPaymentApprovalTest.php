<?php

namespace Tests\Feature;

use App\Models\EscrowTransaction;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;
use App\Models\UserWallet;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualPaymentApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_approve_manual_payment_request(): void
    {
        $customer = User::factory()->create([
            'user_type' => 'customer',
            'status' => 'active',
        ]);

        $admin = User::factory()->create([
            'user_type' => 'admin',
            'status' => 'active',
        ]);

        $transaction = EscrowTransaction::create([
            'user_id' => $customer->id,
            'rider_profile_id' => 0,
            'balance' => 2500,
            'platform_fee' => 0,
            'rider_payout' => 0,
            'status' => EscrowTransaction::STATUS_PENDING,
            'manual_payment_notified' => true,
            'payment_method' => 'bank_transfer',
        ]);

        $response = $this->actingAs($admin, 'web')
            ->withSession(['_token' => 'test-token'])
            ->post(route('admin.manual-payments.approve', $transaction), [
                '_token' => 'test-token',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $transaction->refresh();
        $this->assertSame(EscrowTransaction::STATUS_HELD, $transaction->status);
    }

    public function test_admin_approval_credits_the_assigned_rider_wallet_for_a_job(): void
    {
        $customer = User::factory()->create([
            'user_type' => 'customer',
            'status' => 'active',
        ]);

        $rider = User::factory()->create([
            'user_type' => 'rider',
            'status' => 'active',
        ]);

        $admin = User::factory()->create([
            'user_type' => 'admin',
            'status' => 'active',
        ]);

        $job = Job::create([
            'user_id' => $customer->id,
            'title' => 'Parcel delivery',
            'description' => 'Need delivery',
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'total_price' => 2500,
            'platform_charge' => 250,
            'order_fee' => 0,
            'price' => 2500,
            'status' => 'open',
            'payment_status' => 'pending',
        ]);

        JobApplication::create([
            'job_id' => $job->id,
            'user_rider_id' => $rider->id,
            'status' => 'accepted',
        ]);

        $transaction = EscrowTransaction::create([
            'user_id' => $customer->id,
            'job_id' => $job->id,
            'rider_profile_id' => 0,
            'balance' => 2500,
            'platform_fee' => 250,
            'rider_payout' => 2250,
            'status' => EscrowTransaction::STATUS_PENDING,
            'manual_payment_notified' => true,
            'payment_method' => 'bank_transfer',
        ]);

        $response = $this->actingAs($admin, 'web')
            ->withSession(['_token' => 'test-token'])
            ->post(route('admin.manual-payments.approve', $transaction), [
                '_token' => 'test-token',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $transaction->refresh();
        $this->assertSame(EscrowTransaction::STATUS_HELD, $transaction->status);

        $wallet = UserWallet::where('user_id', $rider->id)->first();
        $this->assertNotNull($wallet);
        $this->assertSame(2250.0, (float) $wallet->balance);

        $this->assertTrue(WalletTransaction::where('user_id', $rider->id)
            ->where('transaction_type', 'credit')
            ->where('purpose', 'job_earnings')
            ->where('amount', 2250)
            ->exists());

        $job->refresh();
        $this->assertSame('in_progress', $job->status);
        $this->assertSame('paid', $job->payment_status);

        $application = JobApplication::where('job_id', $job->id)
            ->where('user_rider_id', $rider->id)
            ->first();
        $this->assertNotNull($application);
        $this->assertSame('in_progress', $application->status);
    }
}
