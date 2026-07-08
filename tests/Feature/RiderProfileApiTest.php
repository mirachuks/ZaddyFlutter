<?php

namespace Tests\Feature;

use App\Models\Guarantor;
use App\Models\RiderProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class RiderProfileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_rider_profile_can_be_created_from_bracketed_guarantor_fields(): void
    {
        $user = User::create([
            'first_name' => 'Rider',
            'last_name' => 'Tester',
            'email' => 'rider.tester@example.com',
            'password' => bcrypt('password123'),
            'mobile_number' => '08012345678',
            'user_type' => 'rider',
            'status' => 'active',
            'is_verified' => 'yes',
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/riders', [
                'service_zone' => 'Lagos',
                'gender' => 'male',
                'state' => 'Lagos',
                'mobility_type' => 'bike',
                'plate_number' => 'ABC123',
                'license_number' => 'LIC-123',
                'guarantors[0][name]' => 'Jane Doe',
                'guarantors[0][phone]' => '08087654321',
                'guarantors[0][email]' => 'jane@example.com',
                'guarantors[0][nin]' => '12345678901',
                'guarantors[0][id_type]' => 'NIN',
                'guarantors[0][relationship]' => 'Sibling',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('rider_profiles', [
            'user_id' => $user->id,
            'legal_name' => 'Rider Tester',
            'first_name' => 'Rider',
            'last_name' => 'Tester',
            'email' => 'rider.tester@example.com',
        ]);

        $this->assertDatabaseMissing('guarantors', [
            'mobile_no' => '08087654321',
        ]);

        $riderProfile = RiderProfile::where('user_id', $user->id)->first();
        $this->assertNotNull($riderProfile);
        $this->assertIsArray($riderProfile->guarantors);
        $this->assertSame('Jane Doe', $riderProfile->guarantors[0]['name']);
        $this->assertSame('08087654321', $riderProfile->guarantors[0]['phone']);
    }

    public function test_rider_profile_can_be_created_without_guarantor_state(): void
    {
        $user = User::create([
            'first_name' => 'State',
            'last_name' => 'Optional',
            'email' => 'rider.state@example.com',
            'password' => bcrypt('password123'),
            'mobile_number' => '08012345670',
            'user_type' => 'rider',
            'status' => 'active',
            'is_verified' => 'yes',
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/riders', [
                'service_zone' => 'Lagos',
                'gender' => 'male',
                'state' => 'Lagos',
                'mobility_type' => 'bike',
                'bike_plate_number' => 'STATE123',
                'license_number' => 'LIC-789',
                'guarantors[0][name]' => 'Jake Doe',
                'guarantors[0][phone]' => '08087654322',
                'guarantors[0][email]' => 'jake@example.com',
                'guarantors[0][nin]' => '12345678903',
                'guarantors[0][id_type]' => 'NIN',
                'guarantors[0][relationship]' => 'Friend',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $riderProfile = RiderProfile::where('user_id', $user->id)->first();
        $this->assertNotNull($riderProfile);
        $this->assertIsArray($riderProfile->guarantors);
        $this->assertSame('Jake Doe', $riderProfile->guarantors[0]['name']);
        $this->assertSame('', $riderProfile->guarantors[0]['state']);
        $this->assertSame('', $riderProfile->guarantors[0]['address']);
    }

    public function test_unverified_riders_cannot_toggle_availability(): void
    {
        $user = User::create([
            'first_name' => 'Offline',
            'last_name' => 'Rider',
            'email' => 'offline.rider@example.com',
            'password' => bcrypt('password123'),
            'mobile_number' => '08012345672',
            'user_type' => 'rider',
            'status' => 'active',
            'is_verified' => 'no',
        ]);

        $riderProfile = RiderProfile::create([
            'user_id' => $user->id,
            'first_name' => 'Offline',
            'last_name' => 'Rider',
            'legal_name' => 'Offline Rider',
            'email' => $user->email,
            'mobile_number' => $user->mobile_number,
            'status' => 'active',
            'is_available' => 'no',
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson('/api/riders/' . $riderProfile->id . '/availability', [
                'current_latitude' => 6.5244,
                'current_longitude' => 3.3792,
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('requires_verification', true);
    }

    public function test_rider_profile_can_be_created_with_passport_as_guarantor_id_type(): void
    {
        $user = User::create([
            'first_name' => 'Passport',
            'last_name' => 'Guarantor',
            'email' => 'rider.passport@example.com',
            'password' => bcrypt('password123'),
            'mobile_number' => '08012345671',
            'user_type' => 'rider',
            'status' => 'active',
            'is_verified' => 'yes',
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/riders', [
                'service_zone' => 'Lagos',
                'gender' => 'male',
                'state' => 'Lagos',
                'mobility_type' => 'bike',
                'bike_plate_number' => 'PASS123',
                'license_number' => 'LIC-456',
                'guarantors[0][name]' => 'Pat Doe',
                'guarantors[0][phone]' => '08087654323',
                'guarantors[0][email]' => 'pat@example.com',
                'guarantors[0][nin]' => 'A1234567',
                'guarantors[0][id_type]' => 'Passport',
                'guarantors[0][relationship]' => 'Friend',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $riderProfile = RiderProfile::where('user_id', $user->id)->first();
        $this->assertNotNull($riderProfile);
        $this->assertIsArray($riderProfile->guarantors);
        $this->assertSame('Pat Doe', $riderProfile->guarantors[0]['name']);
        $this->assertSame('08087654323', $riderProfile->guarantors[0]['phone']);
        $this->assertSame('A1234567', $riderProfile->guarantors[0]['nin']);
        $this->assertSame('Passport', $riderProfile->guarantors[0]['id_type']);
    }
}
