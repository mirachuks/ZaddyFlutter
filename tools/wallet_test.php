<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

// Bootstrap the application for console usage so Eloquent and DB connections work
$consoleKernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$consoleKernel->bootstrap();

// Use models/controllers
use App\Models\User;
use App\Models\RiderProfile;
use Tymon\JWTAuth\JWTAuth;
use App\Http\Controllers\Wallet\UserWalletController;

// Change this user id as needed
$arg = $argv[1] ?? 2;

if ($arg === 'rider') {
    $rider = RiderProfile::first();
    if (! $rider) {
        echo json_encode(['error' => 'No rider profiles found']);
        exit(1);
    }
    $userId = $rider->user_id;
} else {
    $userId = (int) $arg;
}

$user = User::find($userId);
if (! $user) {
    echo json_encode(['error' => 'User not found', 'user_id' => $userId]);
    exit(1);
}

$wallet = UserWalletController::ensureWallet($user->id);
if (! $wallet) {
    echo json_encode(['error' => 'Wallet not found or could not be created']);
    exit(1);
}

echo json_encode([
    'wallet_id' => $wallet->id,
    'user_id' => $wallet->user_id,
    'wallet_no' => $wallet->wallet_no,
    'balance' => number_format($wallet->balance, 2),
    'token' => Tymon\JWTAuth\Facades\JWTAuth::fromUser($user),
]);
