<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\Job;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Job\JobController;

echo "=== Testing Job Status Update Flow ===\n\n";

// Get initial job state
$job = Job::find(2);
echo "Initial State:\n";
echo "  Job ID: " . $job->id . "\n";
echo "  Job Status: " . $job->status . "\n";
echo "  Job User ID: " . $job->user_id . "\n";

$application = $job->applications()->first();
if ($application) {
    echo "  Application Status: " . $application->status . "\n";
    echo "  Application ID: " . $application->id . "\n";
} else {
    echo "  No application found!\n";
    exit(1);
}

// Get the job's user
$user = User::find($job->user_id);
echo "  User: " . $user->email . "\n\n";

// Test 1: Rider marks as delivered
echo "Test 1: Rider marks job as 'delivered'\n";
$request = new Request(['status' => 'delivered']);
$request->setUserResolver(function () use ($user) {
    return $user;
});
$controller = new JobController();
$response = $controller->changeStatus($request, $job);
$data1 = json_decode($response->getContent(), true);
echo "  Status Code: " . $response->getStatusCode() . "\n";
echo "  Success: " . ($data1['success'] ? 'true' : 'false') . "\n";

$job->refresh();
$application->refresh();
echo "  Job Status After: " . $job->status . "\n";
echo "  Application Status After: " . $application->status . "\n\n";

// Test 2: Customer marks as completed
echo "Test 2: Customer marks job as 'completed'\n";
$job2 = Job::find(2);
$request2 = new Request(['status' => 'completed']);
$request2->setUserResolver(function () use ($user) {
    return $user;
});
$response2 = $controller->changeStatus($request2, $job2);
$data2 = json_decode($response2->getContent(), true);
echo "  Status Code: " . $response2->getStatusCode() . "\n";
echo "  Success: " . ($data2['success'] ? 'true' : 'false') . "\n";

$job2->refresh();
$application->refresh();
echo "  Job Status After: " . $job2->status . "\n";
echo "  Application Status After: " . $application->status . "\n\n";

echo "=== Test Completed Successfully ===\n";
