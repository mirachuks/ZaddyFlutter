<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo 'DB=' . DB::connection()->getDatabaseName() . PHP_EOL;
$jobs = DB::select('SHOW COLUMNS FROM jobs');
foreach ($jobs as $c) {
    echo $c->Field . ' ' . $c->Type . ' ' . $c->Null . ' ' . var_export($c->Default, true) . PHP_EOL;
}
$escrow = DB::select('SHOW COLUMNS FROM escrow_transactions');
foreach ($escrow as $c) {
    echo $c->Field . ' ' . $c->Type . ' ' . $c->Null . ' ' . var_export($c->Default, true) . PHP_EOL;
}
