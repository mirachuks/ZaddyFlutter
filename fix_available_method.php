#!/usr/bin/env php
<?php
// Fix the JobController available() method

$filePath = __DIR__ . '/app/Http/Controllers/Job/JobController.php';
$content = file_get_contents($filePath);

// Find the line with "public function available"
$lines = explode("\n", $content);

$methodStart = -1;
$methodEnd = -1;
$braceCount = 0;

foreach ($lines as $i => $line) {
    if (str_contains($line, 'public function available(Request $request)')) {
        $methodStart = $i;
        break;
    }
}

if ($methodStart === -1) {
    echo "ERROR: Could not find available() method\n";
    exit(1);
}

echo "Found available() method at line " . ($methodStart + 1) . "\n";

// Find the matching closing brace
$braceCount = 0;
for ($i = $methodStart; $i < count($lines); $i++) {
    $line = $lines[$i];
    $braceCount += substr_count($line, '{');
    $braceCount -= substr_count($line, '}');

    if ($braceCount === 0 && $i > $methodStart) {
        $methodEnd = $i;
        break;
    }
}

if ($methodEnd === -1) {
    echo "ERROR: Could not find closing brace for available() method\n";
    exit(1);
}

echo "Method ends at line " . ($methodEnd + 1) . "\n";

// Replace the method
$newMethod = <<<'METHOD'
    public function available(Request $request): JsonResponse
    {
        $latitude = $request->float('latitude');
        $longitude = $request->float('longitude');
        $radius = max(1, (int) $request->get('radius', 10));

        $query = Job::query()
            ->where('status', 'open')
            ->with('user:id,name,email,mobile_number')
            ->orderByDesc('created_at');

        // Database driver check MUST be here BEFORE adding selectRaw
        $driverName = DB::connection()->getDriverName();

        // Only apply Haversine formula on MySQL/MariaDB
        if ($latitude !== null && $longitude !== null && ($driverName === 'mysql' || $driverName === 'mariadb')) {
            $query->whereNotNull('pickup_lat')
                ->whereNotNull('pickup_lng');

            $earthRadiusKm = 6371;
            $latitudeRad = deg2rad($latitude);
            $longitudeRad = deg2rad($longitude);

            $query->selectRaw(
                'jobs.*, ( ? * acos(
                    cos(?) * cos(radians(pickup_lat)) * cos(radians(pickup_lng) - ?) +
                    sin(?) * sin(radians(pickup_lat))
                )) AS distance',
                [$earthRadiusKm, $latitudeRad, $longitudeRad, $latitudeRad]
            )->having('distance', '<=', $radius)
            ->orderBy('distance');
        }

        $jobs = $query->get();

        return response()->json([
            'success' => true,
            'count' => $jobs->count(),
            'data' => $jobs,
        ], 200);
    }
METHOD;

// Replace lines from methodStart to methodEnd
array_splice($lines, $methodStart, $methodEnd - $methodStart + 1, [$newMethod]);

// Write back
$newContent = implode("\n", $lines);
file_put_contents($filePath, $newContent);

echo "SUCCESS: available() method has been replaced\n";
echo "File saved to: $filePath\n";
?>