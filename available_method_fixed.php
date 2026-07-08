<?php

// THIS IS A CORRECTED VERSION OF THE AVAILABLE METHOD
// Use this to replace the broken one in JobController.php

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
    // For SQLite, query builder will just return all open jobs without distance calc

    $jobs = $query->get();

    return response()->json([
        'success' => true,
        'count' => $jobs->count(),
        'data' => $jobs,
    ], 200);
}
