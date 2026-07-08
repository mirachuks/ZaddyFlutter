<?php
require 'bootstrap/app.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Http\Request;

// Test the rating endpoint with a delivered job
$jobId = 2; // The job that we delivered

// Create a request
$request = Request::create('/api/reviews', 'POST', [
    'job_id' => $jobId,
    'score' => 5,
    'review' => 'Great delivery! The rider was professional and arrived on time.',
]);

// Get the customer user and set authentication
$customer = \App\Models\User::find(1);
if (!$customer) {
    echo "Customer not found\n";
    exit;
}

// Set the authenticated user
$request->setUserResolver(function () use ($customer) {
    return $customer;
});

// Check the job
$job = \App\Models\Job::with('riderApplication')->find($jobId);
echo "=== JOB DETAILS ===\n";
echo "Job ID: " . $job->id . "\n";
echo "Job Status: " . $job->status . "\n";
echo "Rider Application Status: " . ($job->riderApplication ? $job->riderApplication->status : 'NOT FOUND') . "\n";
echo "Rider User ID: " . ($job->riderApplication ? $job->riderApplication->user_rider_id : 'N/A') . "\n\n";

// Try to submit the rating
$controller = new \App\Http\Controllers\Review\ReviewController();
try {
    $response = $controller->storeFromJob($request);
    $data = json_decode($response->getContent(), true);

    echo "=== RATING SUBMISSION ===\n";
    echo "Status: " . $response->status() . "\n";
    echo "Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";

    if ($response->status() === 201) {
        echo "\n✓ Rating submitted successfully!\n";
    } else {
        echo "\n✗ Rating submission failed\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
