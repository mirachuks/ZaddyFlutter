<?php
require "vendor/autoload.php";
$c = new ReflectionClass("App\\Http\\Controllers\\Job\\JobController");
echo $c->getFileName(), PHP_EOL;
echo $c->hasMethod("available") ? "has available" : "missing available";
echo PHP_EOL;
foreach ($c->getMethods() as $m) {
    if (strpos($m->getName(), 'avail') !== false) {
        echo $m->getName(), PHP_EOL;
    }
}
