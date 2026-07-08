<?php

namespace App\Http\Controllers\Job;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AppNotification;
use App\Models\Job;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class JobController extends Controller
{
    // =====================================================================
    // INDEX — List all jobs (public with filters)
    // GET /api/jobs
    // =====================================================================
    public function index(Request $request): JsonResponse
    {
        $query = Job::with('user:id,name,email,mobile_number');
 
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
 
        if ($request->filled('mobility_type_needed')) {
            $query->where('mobility_type_needed', $request->mobility_type_needed);
        }
 
        if ($request->filled('price_type')) {
            $query->where('price_type', $request->price_type);
        }
 
        if ($request->filled('pickup_address')) {
            $query->where('pickup_address', 'like', '%' . $request->pickup_address . '%');
        }
 
        if ($request->filled('dropoff_address')) {
            $query->where('dropoff_address', 'like', '%' . $request->dropoff_address . '%');
        }
 
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('pickup_address', 'like', "%{$search}%")
                  ->orWhere('dropoff_address', 'like', "%{$search}%");
            });
        }
 
        if ($request->boolean('active_only', false)) {
            $query->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
        }
 
        if ($request->filled('expires_after')) {
            $query->where('expires_at', '>', $request->expires_after);
        }
 
        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
 
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }
 
        $sortBy       = $request->get('sort_by', 'posted_at');
        $sortOrder    = $request->get('sort_order', 'desc');
        $allowedSorts = ['posted_at', 'created_at', 'price', 'expires_at', 'title'];
 
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        }
 
        $perPage = min((int) $request->get('per_page', 15), 100);
        $jobs    = $query->paginate($perPage);
 
        return response()->json([
            'success' => true,
            'data'    => $jobs,
        ]);
    }
 
    // =========================================================================
    // STORE — Post job(s) from parcels
    // POST /api/jobs
    // Accepts: Single job OR Array of parcels for multi-parcel delivery
    // =========================================================================
    public function store(Request $request): JsonResponse
    {
        // Check if this is a multi-parcel request
        $isMultiParcel = $request->has('parcels') && is_array($request->get('parcels'));

        if ($isMultiParcel) {
            return $this->storeMultipleParcels($request);
        } else {
            return $this->storeSingleJob($request);
        }
    }

    // =========================================================================
    // STORE SINGLE JOB (Backward compatible)
    // =========================================================================
    private function storeSingleJob(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id'              => ['required', 'integer', 'exists:users,id'],
            'title'                => ['required', 'string', 'max:255'],
            'description'          => ['nullable', 'string', 'max:5000'],
            'pickup_address'       => ['required', 'string', 'max:500'],
            'pickup_lat'           => ['nullable', 'numeric', 'between:-90,90'],
            'pickup_lng'           => ['nullable', 'numeric', 'between:-180,180'],
            'dropoff_address'      => ['required', 'string', 'max:500'],
            'mobility_type_needed' => ['nullable', 'string', Rule::in(['bike', 'van'])],
            'price'                => ['nullable', 'numeric', 'min:0'],
            'price_type'           => ['sometimes', 'string', Rule::in(['fixed', 'negotiable'])],
            'expires_at'           => ['nullable', 'date', 'after:now'],
        ]);
 
        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }
 
        $data               = $validator->validated();
        $data['status']     = 'open';
        $data['posted_at']  = now();
        $data['expires_at'] = $data['expires_at'] ?? Carbon::now()->addMinutes(10);
 
        $job = Job::create($data);
 
        return response()->json([
            'success'   => true,
            'message'   => 'Job posted successfully.',
            'job_count' => 1,
            'data'      => [$job->load('user:id,name,email,mobile_number')],
        ], 201);
    }

    // =========================================================================
    // STORE MULTIPLE PARCELS (NEW - for multi-parcel delivery)
    // =========================================================================
    private function storeMultipleParcels(Request $request): JsonResponse
    {
        // Validation rules for parcel array
        $validator = Validator::make($request->all(), [
            'user_id'           => ['required', 'integer', 'exists:users,id'],
            'price'             => ['required', 'numeric', 'min:0'],
            'price_type'        => ['sometimes', 'string', Rule::in(['fixed', 'negotiable'])],
            'expires_at'        => ['nullable', 'date', 'after:now'],
            
            // Parcel array validation
            'parcels'           => ['required', 'array', 'min:1'],
            'parcels.*'         => ['required', 'array'],
            'parcels.*.parcel_number' => ['required', 'integer', 'min:1'],
            'parcels.*.title'         => ['required', 'string', 'max:255'],
            'parcels.*.pickup_address' => ['required', 'string', 'max:500'],
            'parcels.*.pickup_lat'    => ['nullable', 'numeric', 'between:-90,90'],
            'parcels.*.pickup_lng'    => ['nullable', 'numeric', 'between:-180,180'],
            'parcels.*.dropoff_address' => ['required', 'string', 'max:500'],
            'parcels.*.item_category'  => ['nullable', 'string', 'max:100'],
            'parcels.*.item_description' => ['nullable', 'string', 'max:500'],
            'parcels.*.recipient_name'  => ['nullable', 'string', 'max:255'],
            'parcels.*.recipient_phone' => ['nullable', 'string', 'max:20'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $parcels = $data['parcels'];
        $userId = $data['user_id'];
        $totalPrice = $data['price'];
        $priceType = $data['price_type'] ?? 'fixed';
        $expiresAt = $data['expires_at'] ?? Carbon::now()->addMinutes(10);

        $createdJobs = [];
        $errors = [];

        try {
            // Loop through each parcel and create a job
            foreach ($parcels as $index => $parcelData) {
                try {
                    $jobData = [
                        'user_id'              => $userId,
                        'title'                => $parcelData['title'],
                        'description'         => $parcelData['item_description'] ?? null,
                        'pickup_address'      => $parcelData['pickup_address'],
                        'pickup_lat'          => $parcelData['pickup_lat'] ?? null,
                        'pickup_lng'          => $parcelData['pickup_lng'] ?? null,
                        'dropoff_address'     => $parcelData['dropoff_address'],
                        'price'               => $totalPrice,
                        'price_type'          => $priceType,
                        'status'              => 'open',
                        'posted_at'           => now(),
                        'expires_at'          => $expiresAt,
                        // Store parcel metadata as JSON for reference
                        'parcel_metadata'     => json_encode([
                            'parcel_number'     => $parcelData['parcel_number'],
                            'item_category'     => $parcelData['item_category'] ?? null,
                            'recipient_name'    => $parcelData['recipient_name'] ?? null,
                            'recipient_phone'   => $parcelData['recipient_phone'] ?? null,
                            'batch_size'        => count($parcels),
                            'batch_created_at'  => now()->toIso8601String(),
                        ]),
                    ];

                    // Create the job
                    $job = Job::create($jobData);
                    $createdJobs[] = $job->fresh()->load('user:id,name,email,mobile_number');

                    \Log::info("Job created for parcel {$parcelData['parcel_number']}", ['job_id' => $job->id]);

                } catch (\Exception $e) {
                    $errors[] = [
                        'parcel_number' => $parcelData['parcel_number'] ?? $index,
                        'error' => $e->getMessage(),
                    ];
                    \Log::error("Failed to create job for parcel {$index}", ['error' => $e->getMessage()]);
                }
            }

            // Check if any jobs were created successfully
            if (empty($createdJobs)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create any jobs',
                    'errors' => $errors,
                ], 422);
            }

            // Return success response with created jobs
            return response()->json([
                'success'     => true,
                'message'     => count($createdJobs) . ' job(s) created successfully.',
                'job_count'   => count($createdJobs),
                'data'        => $createdJobs,
                'failed_count' => count($errors),
                'errors'      => !empty($errors) ? $errors : null,
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Error creating multi-parcel jobs', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error creating jobs: ' . $e->getMessage(),
                'created_count' => count($createdJobs),
                'data' => !empty($createdJobs) ? $createdJobs : null,
            ], 500);
        }
    }

    // =========================================================================
    // SHOW — Get job details
    // =========================================================================
    public function show(Job $job): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $job->load([
                'user:id,name,email,mobile_number',
            ]),
        ]);
    }

    public function available(Request $request): JsonResponse
    {
        $latitude = $request->float('latitude');
        $longitude = $request->float('longitude');
        $radius = max(1, (int) $request->get('radius', 10));

        $query = Job::query()
            ->where('status', 'open')
            ->with('user:id,name,email,mobile_number')
            ->orderByDesc('created_at');

        if ($latitude !== null && $longitude !== null) {
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

    // =========================================================================
    // UPDATE — Update job details
    // =========================================================================
    public function update(Request $request, Job $job): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title'                => ['sometimes', 'string', 'max:255'],
            'description'          => ['sometimes', 'nullable', 'string', 'max:5000'],
            'pickup_address'       => ['sometimes', 'string', 'max:500'],
            'dropoff_address'      => ['sometimes', 'string', 'max:500'],
            'price'                => ['sometimes', 'numeric', 'min:0'],
            'status'               => ['sometimes', Rule::in(['open', 'matched', 'in_progress', 'completed', 'cancelled'])],
            'expires_at'           => ['sometimes', 'date', 'after:now'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $job->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Job updated successfully.',
            'data' => $job->load('user:id,name,email,mobile_number'),
        ]);
    }

    // =========================================================================
    // DESTROY — Delete job
    // =========================================================================
    public function destroy(Job $job): JsonResponse
    {
        $job->delete();

        return response()->json([
            'success' => true,
            'message' => 'Job deleted successfully.',
        ]);
    }

    // =========================================================================
    // Helper: Validation Error Response
    // =========================================================================
    private function validationError($errors): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $errors,
        ], 422);
    }
}
?>
