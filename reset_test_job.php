<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\Job;
use App\Models\User;

// Reset job 2 to 'in_progress' for clean testing
$job = Job::find(2);
if ($job) {
    $job->update([
        'status' => 'in_progress',
        'delivered_at' => null
    ]);

    // Reset application status
    $application = $job->applications()->first();
    if ($application) {
        $application->update(['status' => 'in_progress']);
    }

    echo "Job 2 reset to 'in_progress' status\n";
    echo "Job Status: " . $job->status . "\n";
    echo "Application Status: " . $application->status . "\n";
} else {
    echo "Job 2 not found\n";
}
