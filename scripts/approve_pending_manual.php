<?php
$vendor = __DIR__ . '/../vendor/autoload.php';
if (! file_exists($vendor)) {
    echo "ERROR: vendor/autoload.php not found. Run composer install in laravel_api.\n";
    exit(1);
}
require $vendor;
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\EscrowTransaction;
use App\Models\Job;
use App\Http\Controllers\Wallet\UserWalletController;
use Illuminate\Support\Facades\DB;

$tx = EscrowTransaction::where('manual_payment_notified', true)
    ->where('status', 'pending')
    ->latest()
    ->first();

if (! $tx) {
    echo "NO_PENDING\n";
    exit(0);
}

try {
    DB::transaction(function () use ($tx) {
        $tx->status = EscrowTransaction::STATUS_HELD;
        $tx->save();

        $job = $tx->job_id ? Job::find($tx->job_id) : null;
        $riderId = null;
        $amountToCredit = $tx->rider_payout ?? null;

        if ($job) {
            if ($job->acceptedApplication) {
                $accepted = $job->acceptedApplication;
                $riderId = $accepted->user_rider_id;

                if ($amountToCredit === null) {
                    $amountToCredit = $tx->balance - ($tx->platform_fee ?? 0);
                }

                if ($accepted->status !== 'in_progress') {
                    $accepted->update(['status' => 'in_progress']);
                }

                if (! in_array($job->status, ['in_progress', 'completed', 'delivered', 'cancelled'])) {
                    $job->status = 'in_progress';
                }
            }

            if ($job->payment_status !== 'paid') {
                $job->payment_status = 'paid';
            }

            $job->save();
        }

        if ($riderId && $amountToCredit > 0) {
            UserWalletController::ensureWallet($riderId);
            UserWalletController::credit([
                'user_id' => $riderId,
                'amount' => $amountToCredit,
                'purpose' => 'job_earnings',
            ]);
        }

        if (! $job) {
            $payerId = $tx->user_id;
            $topupAmount = $tx->balance ?? 0;

            if ($payerId && $topupAmount > 0) {
                UserWalletController::ensureWallet($payerId);
                UserWalletController::credit([
                    'user_id' => $payerId,
                    'amount' => $topupAmount,
                    'purpose' => 'topup',
                ]);
            }
        }
    });

    echo "APPROVED: {$tx->id}\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
