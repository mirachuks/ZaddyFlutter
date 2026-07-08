<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\Job;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Job\JobController;

echo "=== COMPLETE END-TO-END TEST ===\n\n";

// Reset job to in_progress
$job = Job::find(2);
$job->update([
    'status' => 'in_progress',
    'delivered_at' => null
]);

$application = $job->applications()->first();
$application->update(['status' => 'in_progress']);

echo "Initial State:\n";
echo "  Job Status: " . $job->status . "\n";
echo "  Application Status: " . $application->status . "\n";

$customer = User::find($job->user_id);
$rider = User::find($application->user_rider_id);

// Step 1: Rider marks as delivered
echo "\n=== Step 1: Rider clicks 'Mark Delivered' ===\n";
$request1 = new Request(['status' => 'delivered']);
$request1->setUserResolver(function () use ($rider) {
    return $rider;
});

$controller = new JobController();
$response1 = $controller->changeStatus($request1, $job);
$data1 = json_decode($response1->getContent(), true);

echo "  Status Code: " . $response1->getStatusCode() . "\n";
echo "  Success: " . ($data1['success'] ? '✓' : '✗') . "\n";

if ($response1->getStatusCode() === 200) {
    $job->refresh();
    $application->refresh();
    echo "  Job Status: " . $job->status . "\n";
    echo "  Application Status: " . $application->status . "\n";
}

// Step 2: Customer marks as completed
echo "\n=== Step 2: Customer clicks 'Confirm Received' ===\n";
$job->refresh();
$request2 = new Request(['status' => 'completed']);
$request2->setUserResolver(function () use ($customer) {
    return $customer;
});

$response2 = $controller->changeStatus($request2, $job);
$data2 = json_decode($response2->getContent(), true);

echo "  Status Code: " . $response2->getStatusCode() . "\n";
echo "  Success: " . ($data2['success'] ? '✓' : '✗') . "\n";

if ($response2->getStatusCode() === 200) {
    $job->refresh();
    $application->refresh();
    echo "  Job Status: " . $job->status . "\n";
    echo "  Application Status: " . $application->status . "\n";
}

echo "\n=== TEST COMPLETED ===\n";
if ($response1->getStatusCode() === 200 && $response2->getStatusCode() === 200) {
    echo "✓ Both rider and customer updates work correctly!\n";
} else {
    echo "✗ One or more updates failed\n";
}
