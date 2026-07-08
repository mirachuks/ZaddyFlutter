<?php

namespace Tests\Feature;

use App\Models\EscrowTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ManualPaymentProofUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_payment_notification_can_store_an_uploaded_payment_proof(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'user_type' => 'customer',
            'status' => 'active',
        ]);

        $file = UploadedFile::fake()->create('payment-proof.png', 1024, 'image/png');

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/payments/manual/notify', [
                'amount' => 2500,
                'description' => 'Bank transfer payment',
                'payment_reference' => 'REF-001',
                'notes' => 'Paid from bank transfer',
                'payment_proof' => $file,
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $transaction = EscrowTransaction::latest('id')->first();
        $this->assertNotNull($transaction);
        $this->assertNotNull($transaction->payment_proof_path);
        $this->assertTrue(Storage::disk('public')->exists($transaction->payment_proof_path));
    }

    public function test_admin_manual_payments_page_shows_inline_payment_proof_preview(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'user_type' => 'admin',
            'status' => 'active',
        ]);

        $file = UploadedFile::fake()->create('proof.png', 1024, 'image/png');
        $path = $file->store('manual-payment-proofs', 'public');

        $transaction = EscrowTransaction::create([
            'user_id' => $admin->id,
            'rider_profile_id' => 0,
            'balance' => 2500,
            'platform_fee' => 0,
            'rider_payout' => 0,
            'status' => EscrowTransaction::STATUS_PENDING,
            'manual_payment_notified' => true,
            'payment_method' => 'bank_transfer',
            'payment_proof_path' => $path,
        ]);

        $response = $this->actingAs($admin, 'web')
            ->get(route('admin.manual-payments'));

        $response->assertOk();
        $response->assertSee('Payment proof');
        $response->assertSee('Open full image');
        $response->assertSee(Storage::disk('public')->url($path));
        $response->assertSee($transaction->payment_proof_path);
    }
}
