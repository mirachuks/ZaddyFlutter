<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Job;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Job\JobController;

// Get a job to test with
$job = Job::first();
if (!$job) {
    echo "No jobs in database\n";
    exit(1);
}

echo "=== Before Update ===\n";
echo "Job ID: " . $job->id . "\n";
echo "Current Status: " . $job->status . "\n";

// Get the job's user for the request
$user = User::find($job->user_id);
echo "User: " . $user->email . "\n";

// Create a request to test
$request = new Request([
    'status' => 'delivered'
]);
$request->setUserResolver(function () use ($user) {
    return $user;
});

// Call the controller method directly
$controller = new JobController();
$response = $controller->changeStatus($request, $job);
$responseData = json_decode($response->getContent(), true);

echo "\n=== Response ===\n";
echo json_encode($responseData, JSON_PRETTY_PRINT) . "\n";

// Refresh the job from database
$job->refresh();
echo "\n=== After Update ===\n";
echo "Job Status: " . $job->status . "\n";

// Check application status
$application = $job->applications()->first();
if ($application) {
    echo "Application Status: " . $application->status . "\n";
}
