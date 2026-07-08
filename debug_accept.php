<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

use Illuminate\Support\Facades\DB;

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Query jobs table
try {
    $jobs = DB::table('jobs')->select('id', 'user_id', 'title', 'status')->limit(5)->get();
    echo "Jobs found: " . count($jobs) . "\n";
    foreach ($jobs as $job) {
        echo "ID: {$job->id}, User: {$job->user_id}, Title: {$job->title}, Status: {$job->status}\n";
    }
} catch (\Exception $e) {
    echo "Error querying jobs: " . $e->getMessage() . "\n";
}

// Check job_applications table
try {
    $apps = DB::table('job_applications')->select('id', 'job_id', 'user_rider_id', 'status')->limit(5)->get();
    echo "\nJob Applications found: " . count($apps) . "\n";
    foreach ($apps as $app) {
        echo "ID: {$app->id}, Job: {$app->job_id}, Rider: {$app->user_rider_id}, Status: {$app->status}\n";
    }
} catch (\Exception $e) {
    echo "Error querying job_applications: " . $e->getMessage() . "\n";
}

echo "\nDone.\n";
