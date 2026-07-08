<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SquadcoWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_updates_withdrawal_status()
    {
        // set webhook secret
        config(['services.squadco.webhook_secret' => 'testsecret']);

        $user = User::create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test.user@example.com',
            'password' => bcrypt('password'),
            'mobile_number' => '0800000000',
            'user_type' => 'rider',
            'status' => 'active',
            'is_verified' => 'yes',
        ]);
        $withdrawal = Withdrawal::create([
            'user_id' => $user->id,
            'amount' => 1000,
            'status' => 'pending',
            'reference' => 'wd_test_123',
        ]);

        $payload = ['reference' => 'wd_test_123', 'status' => 'processed'];
        $raw = json_encode($payload);
        $signature = hash_hmac('sha256', $raw, config('services.squadco.webhook_secret'));

        $response = $this->withHeaders(['X-Squadco-Signature' => $signature])->postJson('/api/webhooks/squadco', $payload);
        $response->assertStatus(200)->assertJson(['received' => true]);

        $this->assertDatabaseHas('withdrawals', ['reference' => 'wd_test_123', 'status' => 'approved']);
    }
}
