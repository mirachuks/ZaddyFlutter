<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$controller = new App\Http\Controllers\Job\JobController;
echo get_class($controller), PHP_EOL;
echo method_exists($controller, 'available') ? 'yes' : 'no', PHP_EOL;
$request = new Illuminate\Http\Request();
$request->merge(['latitude' => 6.5244, 'longitude' => 3.3792, 'radius' => 10]);
$response = $controller->available($request);
echo $response->getContent(), PHP_EOL;
