<?php
$file = file_get_contents('app/Http/Controllers/Job/JobController.php');

echo "=== Checking JobController.php ===\n";

if (strpos($file, 'DB::connection()->getDriverName()') !== false) {
    echo "✓ FOUND: DB::connection()->getDriverName()\n";
} else {
    echo "✗ NOT FOUND: DB::connection()->getDriverName()\n";
}

if (strpos($file, 'selectRaw') !== false) {
    echo "✓ FOUND: selectRaw\n";
} else {
    echo "✗ NOT FOUND: selectRaw\n";
}

// Extract the available method
if (preg_match('/public function available\(Request \$request\).*?(\{.*?\n    \})/s', $file, $matches)) {
    $method = $matches[0];
    echo "\nMethod extracted, length: " . strlen($method) . " chars\n";

    // Check for driver check
    if (strpos($method, 'getDriverName') !== false && strpos($method, 'selectRaw') !== false) {
        echo "✓ Driver check and selectRaw found in same method\n";

        // Check the conditional
        if (preg_match('/if \([^{]*getDriverName[^{]*\) \{/s', $method)) {
            echo "✓ Driver check appears to be in if condition\n";
        } else {
            echo "✗ Driver check might not be in if condition\n";
        }
    }
}
