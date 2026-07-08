<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$email = 'copilot_test_user@example.com';

$u = User::where('email', $email)->first();
if (!$u) {
    $u = User::create([
        'first_name' => 'Copilot',
        'last_name' => 'Test',
        'email' => $email,
        'password' => Hash::make('password'),
        'status' => 'active',
        'is_verified' => 'yes',
        'mobile_number' => '08000000000',
        'user_type' => 'user',
    ]);
}

echo "USER_ID:" . $u->id . PHP_EOL;
echo "TOKEN:" . \Tymon\JWTAuth\Facades\JWTAuth::fromUser($u) . PHP_EOL;
