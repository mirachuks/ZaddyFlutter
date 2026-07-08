<?php

namespace Tests\Feature;

use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobPostingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_posting_stores_title_category_and_receiver_fields(): void
    {
        $payload = [
            'price' => 2500,
            'price_type' => 'fixed',
            'items' => [[
                'title' => 'Laptop delivery',
                'item_category' => 'Electronics',
                'description' => 'Fragile item',
                'pickup_address' => 'Lagos',
                'dropoff_address' => 'Abuja',
                'receiver_name' => 'Ada Lovelace',
                'receiver_phone' => '08012345678',
            ]],
        ];

        $response = $this->postJson('/api/jobs/store', $payload);

        $response->assertCreated();
        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('job_items', [
            'title' => 'Laptop delivery',
            'item_category' => 'Electronics',
            'receiver_name' => 'Ada Lovelace',
            'receiver_phone' => '08012345678',
        ]);

        $this->assertDatabaseHas('jobs', [
            'title' => 'Laptop delivery',
        ]);
    }

    public function test_rider_can_fetch_available_jobs_by_coordinates(): void
    {
        $user = User::create([
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
            'mobile_number' => '08012345678',
            'user_type' => 'user',
            'is_verified' => 'yes',
        ]);

        Job::create([
            'user_id' => $user->id,
            'title' => 'Urgent parcel',
            'status' => 'open',
            'pickup_lat' => 6.5244,
            'pickup_lng' => 3.3792,
            'dropoff_lat' => 6.5300,
            'dropoff_lng' => 3.3900,
            'price' => 2500,
        ]);

        $response = $this->getJson('/api/jobs/available?latitude=6.5244&longitude=3.3792&radius=10');

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonCount(1, 'data');
    }
}
