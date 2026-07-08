<?php

namespace Tests\Feature;

use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;
    public function test_admin_login_page_is_accessible(): void
    {
        $response = $this->get('/admin/login');

        $response->assertStatus(200);
        $response->assertSee('Admin Login');
    }

    public function test_admin_register_page_is_accessible(): void
    {
        $response = $this->get('/admin/register');

        $response->assertStatus(200);
        $response->assertSee('Create Admin Account');
    }

    public function test_dashboard_does_not_crash_when_optional_status_columns_are_missing(): void
    {
        $admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'Tester',
            'email' => 'admin-dashboard-test@example.com',
            'password' => bcrypt('secret1234'),
            'mobile_number' => '08000000000',
            'user_type' => 'admin',
            'status' => 'active',
            'is_verified' => 1,
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Admin dashboard');
    }

    public function test_active_users_page_is_accessible_to_admins(): void
    {
        $admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'Tester',
            'email' => 'active-users-admin@example.com',
            'password' => bcrypt('secret1234'),
            'mobile_number' => '08000000001',
            'user_type' => 'admin',
            'status' => 'active',
            'is_verified' => 1,
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin/active-users');

        $response->assertStatus(200);
        $response->assertSee('Active users');
    }

    public function test_riders_page_lists_riders_from_the_users_table(): void
    {
        $admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'Tester',
            'email' => 'rider-list-admin@example.com',
            'password' => bcrypt('secret1234'),
            'mobile_number' => '08000000002',
            'user_type' => 'admin',
            'status' => 'active',
            'is_verified' => 1,
        ]);

        $rider = User::create([
            'first_name' => 'Rider',
            'last_name' => 'One',
            'email' => 'rider-one@example.com',
            'password' => bcrypt('secret1234'),
            'mobile_number' => '08000000003',
            'user_type' => 'rider',
            'status' => 'active',
            'is_verified' => 1,
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin/riders');

        $response->assertStatus(200);
        $response->assertSee('Rider management');
        $response->assertSee('Rider One');
    }

    public function test_reports_page_is_accessible_even_when_wallet_columns_are_missing(): void
    {
        $admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'Reports',
            'email' => 'reports-admin@example.com',
            'password' => bcrypt('secret1234'),
            'mobile_number' => '08000000006',
            'user_type' => 'admin',
            'status' => 'active',
            'is_verified' => 1,
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin/reports');

        $response->assertStatus(200);
        $response->assertSee('Commission and platform charges');
    }

    public function test_orders_page_can_search_by_customer_email_and_sort_by_date(): void
    {
        $admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'Orders',
            'email' => 'orders-admin@example.com',
            'password' => bcrypt('secret1234'),
            'mobile_number' => '08000000007',
            'user_type' => 'admin',
            'status' => 'active',
            'is_verified' => 1,
        ]);

        $customer = User::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'password' => bcrypt('secret1234'),
            'mobile_number' => '08000000008',
            'user_type' => 'user',
            'status' => 'active',
            'is_verified' => 1,
        ]);

        Job::create([
            'user_id' => $customer->id,
            'title' => 'Urgent delivery',
            'description' => 'Need a quick drop-off',
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'price' => 50,
            'platform_charge' => 5,
            'order_fee' => 1,
            'total_price' => 56,
            'status' => 'open',
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin/orders?search=jane@example.com&sort=latest');

        $response->assertStatus(200);
        $response->assertSee('jane@example.com');
        $response->assertSee('Urgent delivery');
    }

    public function test_admin_cannot_suspend_another_admin(): void
    {
        $admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'One',
            'email' => 'admin-one@example.com',
            'password' => bcrypt('secret1234'),
            'mobile_number' => '08000000004',
            'user_type' => 'admin',
            'status' => 'active',
            'is_verified' => 1,
        ]);

        $otherAdmin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'Two',
            'email' => 'admin-two@example.com',
            'password' => bcrypt('secret1234'),
            'mobile_number' => '08000000005',
            'user_type' => 'admin',
            'status' => 'active',
            'is_verified' => 1,
        ]);

        $this->actingAs($admin);

        $response = $this->post('/admin/users/' . $otherAdmin->id . '/status', ['status' => 'suspended']);

        $response->assertSessionHasErrors();
        $this->assertDatabaseHas('users', ['id' => $otherAdmin->id, 'status' => 'active']);
    }
}
