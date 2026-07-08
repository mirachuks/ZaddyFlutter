<?php
// Test the rating endpoint using cURL to make an HTTP request

$jobId = 2;
$rating = 5;
$review = "Great delivery! The rider was professional and arrived on time.";

// Get a valid token - first let's login as customer (user 1)
$loginData = [
    'email' => 'customer@test.com',
    'password' => 'password123'
];

$ch = curl_init('http://localhost/api/auth/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$loginResponse = json_decode($response, true);

echo "=== LOGIN TEST ===\n";
echo "Status: $httpCode\n";
if ($httpCode !== 200 || !isset($loginResponse['data']['token'])) {
    echo "Failed to login\n";
    echo "Response: " . json_encode($loginResponse, JSON_PRETTY_PRINT) . "\n";
    exit;
}

$token = $loginResponse['data']['token'];
echo "Token received: " . substr($token, 0, 20) . "...\n\n";

// Now test the rating endpoint
$ratingData = [
    'job_id' => (int)$jobId,
    'score' => (int)$rating,
    'review' => $review
];

$ch = curl_init('http://localhost/api/reviews');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($ratingData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    "Authorization: Bearer $token"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$ratingResponse = json_decode($response, true);

echo "=== RATING SUBMISSION TEST ===\n";
echo "Job ID: $jobId\n";
echo "Rating: $rating stars\n";
echo "Status Code: $httpCode\n";
echo "Response: " . json_encode($ratingResponse, JSON_PRETTY_PRINT) . "\n";

if ($httpCode === 201) {
    echo "\n✓ Rating submitted successfully!\n";
} else {
    echo "\n✗ Rating submission failed\n";
}
