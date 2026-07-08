<?php
require 'vendor/autoload.php';
$rc = new ReflectionClass('App\\Http\\Controllers\\Job\\JobController');
echo $rc->getFileName(), PHP_EOL;
foreach ($rc->getMethods() as $m) {
    echo $m->getName(), PHP_EOL;
}
