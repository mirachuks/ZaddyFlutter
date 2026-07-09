<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Job;

$jobId = 2;
$job = Job::with('applications')->find($jobId);
if (! $job) {
    echo "JOB NOT FOUND\n";
    exit(1);
}

echo "JOB ID={$job->id}\n";
echo "JOB STATUS={$job->status}\n";
foreach ($job->applications as $app) {
    echo "APPLICATION ID={$app->id} STATUS={$app->status} RIDER={$app->user_rider_id}\n";
}
