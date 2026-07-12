<?php
require __DIR__."/vendor/autoload.php";
$app = require __DIR__."/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$types = ["user", "customer", "rider", "admin"];
foreach ($types as $type) {
    $users = \App\Models\User::where("user_type", $type)->take(5)->get();
    foreach ($users as $u) {
        echo "$type: {$u->id} {$u->email} {$u->user_type}\n";
    }
}

