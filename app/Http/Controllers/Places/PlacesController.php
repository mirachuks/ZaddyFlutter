<?php

namespace App\Http\Controllers\Places;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PlacesController extends Controller
{
    public function suggest(Request $request)
    {
        $query = trim((string) $request->query('query', ''));

        if ($query === '') {
            return response()->json([]);
        }

        $queryText = str_contains(strtolower($query), 'enugu') ? $query : 'Enugu ' . $query;

        $url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&limit=8&addressdetails=1&countrycodes=ng&bounded=1&viewbox=7.26,6.35,7.60,6.65&q=' . urlencode($queryText);

        $response = file_get_contents($url, false, stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: ZaddyExpress/1.0 (support@zaddyexpress.com)\r\nAccept-Language: en\r\n",
                'timeout' => 20,
            ],
        ]));

        if ($response === false) {
            return response()->json([]);
        }

        $data = json_decode($response, true);

        if (!is_array($data)) {
            return response()->json([]);
        }

        $filtered = array_values(array_filter($data, function ($item) {
            if (!is_array($item)) {
                return false;
            }

            $displayName = strtolower((string) ($item['display_name'] ?? ''));
            if ($displayName === '') {
                return false;
            }

            $placeType = strtolower((string) ($item['type'] ?? ''));
            $placeClass = strtolower((string) ($item['class'] ?? ''));
            $blockedTypes = ['administrative', 'city', 'state', 'county', 'country', 'town', 'village', 'suburb', 'neighbourhood', 'hamlet'];
            $blockedClasses = ['boundary', 'place'];

            if (in_array($placeType, $blockedTypes, true) || in_array($placeClass, $blockedClasses, true)) {
                return false;
            }

            return str_contains($displayName, 'road')
                || str_contains($displayName, 'street')
                || str_contains($displayName, 'avenue')
                || str_contains($displayName, 'close')
                || str_contains($displayName, 'lane')
                || str_contains($displayName, 'crescent')
                || str_contains($displayName, 'drive')
                || str_contains($displayName, 'way')
                || str_contains($displayName, 'estate')
                || str_contains($displayName, 'junction')
                || str_contains($displayName, 'layout')
                || str_contains($displayName, 'market')
                || str_contains($displayName, 'bank')
                || str_contains($displayName, 'church')
                || str_contains($displayName, 'hospital')
                || str_contains($displayName, 'school')
                || str_contains($displayName, 'enugu');
        }));

        return response()->json($filtered);
    }
}
