<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Models\User;

$jobId = 2;
$userId = 1; // use customer id
$token = 'PLACEHOLDER';

// Attempt to get bearer token from user login if possible
// Replace these with real credentials if available
$loginUrl = 'http://localhost/api/user/login';
$loginData = [
    'email' => 'customer@test.com',
    'password' => 'password123',
    'user_type' => 'user',
];

$ch = curl_init($loginUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$responseJson = json_decode($response, true);

echo "LOGIN STATUS: $httpCode\n";
echo "LOGIN RESPONSE: " . json_encode($responseJson, JSON_PRETTY_PRINT) . "\n";
if ($httpCode === 200 && isset($responseJson['data']['token'])) {
    $token = $responseJson['data']['token'];
} else {
    echo "Could not get token, using placeholder.\n";
}

$reviewUrl = 'http://localhost/api/reviews';
$reviewData = [
    'job_id' => $jobId,
    'score' => 4,
    'review' => 'The rider is good',
];

$ch = curl_init($reviewUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($reviewData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    "Authorization: Bearer $token",
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "REVIEW STATUS: $httpCode\n";
echo "REVIEW RESPONSE: " . $response . "\n";
