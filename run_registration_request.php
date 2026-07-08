<?php
require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::firstOrCreate(
    ['email' => 'rider.tester@example.com'],
    [
        'first_name' => 'Rider',
        'last_name' => 'Tester',
        'password' => bcrypt('password123'),
        'mobile_number' => '08012345678',
        'user_type' => 'rider',
        'status' => 'active',
        'is_verified' => 'yes',
    ]
);

$token = Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);

$client = new GuzzleHttp\Client();
$response = $client->post('http://127.0.0.1:8000/api/riders', [
    'headers' => [
        'Authorization' => 'Bearer ' . $token,
        'Accept' => 'application/json',
    ],
    'form_params' => [
        'service_zone' => 'Lagos',
        'gender' => 'male',
        'state' => 'Lagos',
        'mobility_type' => 'bike',
        'plate_number' => 'ABC999',
        'license_number' => 'LIC-123',
        'guarantors[0][name]' => 'Jane Doe',
        'guarantors[0][phone]' => '08087654321',
        'guarantors[0][email]' => 'jane@example.com',
        'guarantors[0][nin]' => '12345678901',
        'guarantors[0][id_type]' => 'NIN',
        'guarantors[0][relationship]' => 'Sibling',
    ],
]);

$body = (string) $response->getBody();
echo 'Status: ' . $response->getStatusCode() . PHP_EOL;
echo $body . PHP_EOL;

$riderProfile = App\Models\RiderProfile::where('user_id', $user->id)->first();
if ($riderProfile) {
    echo 'RiderProfileID: ' . $riderProfile->id . PHP_EOL;
    echo 'PlateNumber: ' . $riderProfile->plate_number . PHP_EOL;
    echo 'LicenseNumber: ' . $riderProfile->license_number . PHP_EOL;
}

$guarantor = App\Models\Guarantor::where('rider_profile_id', $riderProfile->id ?? 0)->first();
if ($guarantor) {
    echo 'GuarantorID: ' . $guarantor->id . PHP_EOL;
    echo 'GuarantorName: ' . $guarantor->name . PHP_EOL;
}
