<?php

namespace Tests\Feature;

use App\Models\Job;
use App\Models\JobItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobApiItemsTest extends TestCase
{
    use RefreshDatabase;

    public function test_rider_job_endpoints_include_attached_items(): void
    {
        $customer = User::create([
            'first_name' => 'Berry',
            'last_name' => 'Yost',
            'email' => 'berry@example.org',
            'password' => bcrypt('password'),
            'mobile_number' => '08017628136',
            'status' => 'active',
            'user_type' => 'user',
            'is_verified' => true,
        ]);
        $job = Job::create([
            'user_id' => $customer->id,
            'title' => 'Multi-parcel delivery',
            'description' => 'Shared job with multiple parcels',
            'pickup_address' => 'Pickup A',
            'dropoff_address' => 'Dropoff B',
            'price' => 50,
            'platform_charge' => 5,
            'total_price' => 55,
            'status' => 'open',
        ]);

        JobItem::create([
            'job_id' => $job->id,
            'title' => 'Parcel 1',
            'receiver_name' => 'Ada',
            'receiver_phone' => '08011111111',
            'pickup_address' => 'Pickup Address One',
            'dropoff_address' => 'Receiver One Address',
        ]);

        JobItem::create([
            'job_id' => $job->id,
            'title' => 'Parcel 2',
            'receiver_name' => 'Grace',
            'receiver_phone' => '08022222222',
            'pickup_address' => 'Pickup Address Two',
            'dropoff_address' => 'Receiver Two Address',
        ]);

        $response = $this->getJson('/api/jobs/available?latitude=6.5244&longitude=3.3792&radius=10');
        $response->assertOk();
        $response->assertJsonPath('data.0.items.0.title', 'Parcel 1');
        $response->assertJsonPath('data.0.items.1.title', 'Parcel 2');

        $myJobsResponse = $this->getJson('/api/my-jobs/' . $customer->id . '?page=1&limit=20');
        $myJobsResponse->assertOk();
        $myJobsResponse->assertJsonPath('data.0.items.0.title', 'Parcel 1');
        $myJobsResponse->assertJsonPath('data.0.items.1.title', 'Parcel 2');

        $rider = User::create([
            'first_name' => 'Rider',
            'last_name' => 'One',
            'email' => 'rider@example.org',
            'password' => bcrypt('password'),
            'mobile_number' => '08033333333',
            'status' => 'active',
            'user_type' => 'rider',
            'is_verified' => true,
        ]);

        $acceptResponse = $this->actingAs($rider)->patchJson("/api/jobs/{$job->id}/accept");
        $acceptResponse->assertOk();
        $acceptResponse->assertJsonPath('data.items.0.title', 'Parcel 1');
        $acceptResponse->assertJsonPath('data.items.1.title', 'Parcel 2');
    }
}
