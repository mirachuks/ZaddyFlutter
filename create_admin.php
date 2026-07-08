<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

$user = User::firstOrCreate(
    ['email' => 'admin@zaddyexpress.com'],
    [
        'first_name' => 'Admin',
        'last_name' => 'User',
        'password' => bcrypt('Admin@123456'),
        'mobile_number' => '08000000000',
        'user_type' => 'admin',
        'status' => 'active',
        'is_verified' => 1,
    ]
);

$user->save();

echo $user->email . PHP_EOL;
echo $user->user_type . PHP_EOL;
