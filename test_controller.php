<?php
$files = glob("app/Http/Controllers/Job/JobController*.php");
foreach ($files as $f) {
    echo $f . ": ";
    $content = file_get_contents($f);
    echo strpos($content, "getDriverName") !== false ? "HAS getDriverName" : "NO getDriverName";
    echo "\n";
}
