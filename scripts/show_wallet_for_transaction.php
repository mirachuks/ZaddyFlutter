<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\EscrowTransaction;
use App\Models\UserWallet;

// Accept optional transaction id as first arg
$txId = $argv[1] ?? null;

if ($txId) {
    $tx = EscrowTransaction::find($txId);
} else {
    // find most recent manual payment that is held or pending
    $tx = EscrowTransaction::where('manual_payment_notified', true)
        ->whereIn('status', ['held', 'pending'])
        ->latest()
        ->first();
}

if (! $tx) {
    echo "No transaction found\n";
    exit(1);
}

echo "Transaction: {$tx->id} status={$tx->status} user_id={$tx->user_id} job_id={$tx->job_id} amount={$tx->balance}\n";

$wallet = UserWallet::where('user_id', $tx->user_id)->first();
if ($wallet) {
    echo "Payer wallet: id={$wallet->id} user_id={$wallet->user_id} balance={$wallet->balance}\n";
} else {
    echo "Payer wallet: none\n";
}

if ($tx->job_id) {
    $riderId = null;
    $job = \App\Models\Job::find($tx->job_id);
    if ($job && $job->acceptedApplication) {
        $riderId = $job->acceptedApplication->user_rider_id;
    }
    if ($riderId) {
        $rwallet = UserWallet::where('user_id', $riderId)->first();
        if ($rwallet) {
            echo "Rider wallet: id={$rwallet->id} user_id={$rwallet->user_id} balance={$rwallet->balance}\n";
        } else {
            echo "Rider wallet: none (rider_id={$riderId})\n";
        }
    }
}
