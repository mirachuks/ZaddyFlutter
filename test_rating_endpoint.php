<?php
require 'bootstrap/app.php';

use Illuminate\Http\Request;

// Create the app
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Get a user with token (the customer who accepted the job)
// Using User 1 (customer) to rate a rider
$customerId = 1; // Customer ID
$jobId = 2; // The job that was delivered

// Simulate a request with authentication
$request = Request::create('/api/reviews', 'POST', [
    'job_id' => $jobId,
    'score' => 5,
    'review' => 'Great delivery! The rider was professional and arrived on time.',
]);

// Get the user and set up authentication
$user = \App\Models\User::find($customerId);
if (!$user) {
    echo "Customer not found\n";
    exit;
}

// Set the authenticated user manually
$request->setUserResolver(function () use ($user) {
    return $user;
});

// Get the controller and call the method
$controller = new \App\Http\Controllers\Review\ReviewController();
try {
    $response = $controller->storeFromJob($request);
    $data = json_decode($response->getContent(), true);

    echo "=== RATING SUBMISSION TEST ===\n";
    echo "Status: " . $response->status() . "\n";
    echo "Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";

    if ($response->status() === 201) {
        echo "\n✓ Rating submitted successfully!\n";
    } else {
        echo "\n✗ Rating submission failed: " . ($data['message'] ?? 'Unknown error') . "\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
