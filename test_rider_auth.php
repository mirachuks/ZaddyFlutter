<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\Job;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Job\JobController;

echo "=== Testing Rider Authorization for Job Status Update ===\n\n";

// Reset job to in_progress
$job = Job::find(2);
$job->update([
    'status' => 'in_progress',
    'delivered_at' => null
]);

$application = $job->applications()->first();
$application->update(['status' => 'in_progress']);

echo "Initial State:\n";
echo "  Job ID: " . $job->id . "\n";
echo "  Job Status: " . $job->status . "\n";
echo "  Job User ID (Customer): " . $job->user_id . "\n";
echo "  Application Status: " . $application->status . "\n";
echo "  Rider ID: " . $application->user_rider_id . "\n\n";

// Get the rider user
$rider = User::find($application->user_rider_id);
echo "  Rider Email: " . $rider->email . "\n\n";

// Test: Rider marks job as delivered
echo "Test: Rider marks job as 'delivered'\n";
$request = new Request(['status' => 'delivered']);
$request->setUserResolver(function () use ($rider) {
    return $rider;
});

$controller = new JobController();
$response = $controller->changeStatus($request, $job);
$data = json_decode($response->getContent(), true);

echo "  Status Code: " . $response->getStatusCode() . "\n";
echo "  Success: " . ($data['success'] ? 'true' : 'false') . "\n";

if (!$data['success']) {
    echo "  Error: " . $data['message'] . "\n";
} else {
    $job->refresh();
    $application->refresh();
    echo "  Job Status After: " . $job->status . "\n";
    echo "  Application Status After: " . $application->status . "\n";
    echo "  Delivered At: " . $job->delivered_at . "\n";
}
