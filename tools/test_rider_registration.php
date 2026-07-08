<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Boot the app (ensure config and facades work)
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Create or get a test user
$userModel = new App\Models\User();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

// Ensure 'name' column exists on users table to satisfy controller's eager load
if (!Schema::hasColumn('users', 'name')) {
    Schema::table('users', function (Blueprint $table) {
        $table->string('name')->nullable();
    });
}
$user = App\Models\User::firstOrCreate(
    ['email' => 'test_rider@example.com'],
    [
        'first_name' => 'Test',
        'last_name' => 'Rider',
        'mobile_number' => '08012345678',
        'password' => bcrypt('password'),
        'status' => 'active',
        'user_type' => 'rider'
    ]
);

// Ensure 'name' is populated for the user model
$user->name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
$user->save();

// Log in as that user
Illuminate\Support\Facades\Auth::loginUsingId($user->id);

// Build request payload matching controller validation
$payload = [
    // user_id will be merged from Auth in controller
    'first_name' => 'Test',
    'last_name' => 'Rider',
    'mobile_number' => '08012345678',
    'nin' => '12345678901',
    'service_zone' => 'Lagos',
    'gender' => 'male',
    'state' => 'Lagos',
    'mobility_type' => 'bike',
    'plate_number' => 'ABC123CD',
    'license_number' => 'LIC123',
    'license_expiry_date' => '2030-12-31',
    'bike_brand' => 'Yamaha',
    'bike_model' => 'YZF',
    'bike_production_year' => '2019',
    'bike_plate_number' => 'ABC123CD',
    'bike_color' => 'Red',
    'bike_engine_number' => 'ENG12345',
    'bike_chassis_number' => 'CHS12345',
    'guarantors' => [
        [
            'name' => 'Guarantor One',
            'phone' => '08011112222',
            'email' => 'g1@example.com',
            'nin' => '09876543210',
            'id_type' => 'NIN',
            'relationship' => 'Friend',
            'state' => 'Lagos',
            'address' => 'Some address'
        ]
    ]
];

$request = Request::create('/api/riders', 'POST', $payload);
// Ensure files and headers are empty; controller uses Auth facade

$controller = new App\Http\Controllers\Rider\RiderProfileController();
$response = $controller->store($request);

echo "Controller response:\n";
if ($response instanceof Illuminate\Http\JsonResponse) {
    echo $response->getContent() . "\n";
} else {
    var_dump($response);
}

// Check DB for created profile
$profile = App\Models\RiderProfile::where('user_id', $user->id)->first();
if ($profile) {
    echo "Rider profile exists: ID={$profile->id}\n";
    echo json_encode($profile->toArray(), JSON_PRETTY_PRINT) . "\n";
} else {
    echo "No rider profile found for user_id={$user->id}\n";
}

// Cleanup: not deleting user/profile to keep record

return 0;
