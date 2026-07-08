<?php
require 'vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;
$capsule->addConnection([
    'driver'    => 'sqlite',
    'database'  => __DIR__ . '/database/database.sqlite',
    'prefix'    => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

use App\Models\Job;
use App\Models\JobApplication;

$jobId = 2;

// Get job and check relationships
$job = Job::with(['applications', 'riderApplication'])->find($jobId);

if (!$job) {
    echo "Job not found\n";
    exit;
}

echo "=== JOB CHECK ===\n";
echo "Job ID: " . $job->id . "\n";
echo "Job Status: " . $job->status . "\n";
echo "Job User ID: " . $job->user_id . "\n\n";

echo "=== ALL APPLICATIONS FOR THIS JOB ===\n";
foreach ($job->applications as $app) {
    echo "  - Application ID: " . $app->id . ", Status: " . $app->status . ", Rider ID: " . $app->user_rider_id . "\n";
}

echo "\n=== RIDER APPLICATION (in_progress/delivered/accepted) ===\n";
if ($job->riderApplication) {
    echo "  Found! ID: " . $job->riderApplication->id . ", Status: " . $job->riderApplication->status . ", Rider ID: " . $job->riderApplication->user_rider_id . "\n";
} else {
    echo "  Not found\n";
}
