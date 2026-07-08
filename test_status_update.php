<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Job;
use App\Models\User;

// Get a job to test with
$job = Job::first();
if (!$job) {
    echo "No jobs in database\n";
    exit(1);
}

echo "Job ID: " . $job->id . "\n";
echo "Current Status: " . $job->status . "\n";
echo "User ID: " . $job->user_id . "\n";

// Get the job's user
$user = User::find($job->user_id);
if (!$user) {
    echo "User not found\n";
    exit(1);
}

echo "Job User: " . $user->email . "\n";

// Get job applications
$applications = $job->applications;
echo "Applications: " . count($applications) . "\n";
foreach ($applications as $app) {
    echo "  - Status: " . $app->status . ", Rider ID: " . $app->user_rider_id . "\n";
}
