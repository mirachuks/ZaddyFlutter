<?php
require __DIR__."/vendor/autoload.php";
$app = require __DIR__."/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$userCount = \App\Models\User::where("user_type","user")->orWhere("user_type","customer")->count();
echo "$userCount users\n";
$riderCount = \App\Models\User::where("user_type","rider")->count();
echo "$riderCount riders\n";
$adminCount = \App\Models\User::where("user_type","admin")->count();
echo "$adminCount admins\n";

