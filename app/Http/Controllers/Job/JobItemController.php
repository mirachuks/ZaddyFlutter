<?php

namespace App\Http\Controllers\Job;

use App\Http\Controllers\Controller;
use App\Models\JobItem;
use App\Models\Job;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class JobItemController extends Controller
{
    // =========================================================================
    // INDEX — Get all job items
    // GET /job-items
    // =========================================================================
    public function index(): JsonResponse
    {
        $jobItems = JobItem::latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'Job items retrieved successfully.',
            'data'    => $jobItems,
        ], 200);
    }


    // =========================================================================
    // STORE — Create a new job item
    // POST /job-items
    // =========================================================================



    public function create(Request $request)
    {
        $requestData = $request->all();

        if (!isset($requestData['items']) && isset($requestData['parcels']) && is_array($requestData['parcels'])) {
            $requestData['items'] = $requestData['parcels'];
        }

        $validator = Validator::make($requestData, [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'user_profile_id' => ['nullable', 'integer', 'exists:users,id'],
            'items'   => ['required', 'array', 'min:1'],
            'items.*.title' => ['required', 'string', 'max:255'],
            'items.*.item_category' => ['nullable', 'string', 'max:5000'],
            'items.*.description' => ['nullable', 'string', 'max:5000'],
            'items.*.item_description' => ['nullable', 'string', 'max:5000'],
            'items.*.receiver_name' => ['nullable', 'string', 'max:255'],
            'items.*.recipient_name' => ['nullable', 'string', 'max:255'],
            'items.*.pickup_address' => ['required', 'string', 'max:500'],
            'items.*.receiver_phone' => ['nullable', 'string', 'max:255'],
            'items.*.recipient_phone' => ['nullable', 'string', 'max:255'],
            'items.*.category' => ['nullable', 'string', 'max:5000'],
            'items.*.pickup_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'items.*.pickup_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'items.*.dropoff_address' => ['required', 'string', 'max:500'],
            'items.*.dropoff_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'items.*.dropoff_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'items.*.mobility_type_needed' => ['nullable', 'string', Rule::in(['bike', 'van', 'truck', 'car'])],
            'items.*.price' => ['nullable', 'numeric', 'min:0'],
            'items.*.price_type' => ['sometimes', 'string', Rule::in(['fixed', 'negotiable'])],
            'items.*.status' => ['nullable', 'string', Rule::in(['open', 'matched', 'in_progress', 'completed', 'cancelled'])],
            'items.*.expires_at' => ['nullable', 'date', 'after:now'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'platform_charge' => ['nullable', 'numeric', 'min:0'],
            'order_fee' => ['nullable', 'numeric', 'min:0'],
            'total_price' => ['nullable', 'numeric', 'min:0'],
            'price_type' => ['nullable', 'string', Rule::in(['fixed', 'negotiable'])],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $createdItems = [];

        try {
            $job = DB::transaction(function () use ($validated, &$createdItems) {
                $jobAttributes = [
                    'user_id' => $validated['user_id'] ?? 1,
                    'title' => $validated['items'][0]['title'] ?? 'Delivery request',
                    'description' => $validated['items'][0]['description'] ?? $validated['items'][0]['item_description'] ?? null,
                    'pickup_address' => $validated['items'][0]['pickup_address'] ?? null,
                    'pickup_lat' => $validated['items'][0]['pickup_lat'] ?? null,
                    'pickup_lng' => $validated['items'][0]['pickup_lng'] ?? null,
                    'dropoff_address' => $validated['items'][0]['dropoff_address'] ?? null,
                    'dropoff_lat' => $validated['items'][0]['dropoff_lat'] ?? null,
                    'dropoff_lng' => $validated['items'][0]['dropoff_lng'] ?? null,
                    'mobility_type_needed' => $validated['items'][0]['mobility_type_needed'] ?? null,
                    'price' => $validated['price'] ?? $validated['estimated_fare'] ?? $validated['items'][0]['price'] ?? 0,
                    'platform_charge' => $validated['platform_charge'] ?? null,
                    'price_type' => $validated['price_type'] ?? $validated['items'][0]['price_type'] ?? 'fixed',
                    'status' => 'open',
                    'posted_at' => now(),
                    'expires_at' => $validated['expires_at'] ?? null,
                ];

                if (Schema::hasColumn('jobs', 'order_fee')) {
                    $jobAttributes['order_fee'] = $validated['order_fee'] ?? null;
                }

                if (Schema::hasColumn('jobs', 'total_price')) {
                    $jobAttributes['total_price'] = $validated['total_price'] ?? null;
                }

                $job = Job::create($jobAttributes);

                foreach ($validated['items'] as $itemData) {
                    $itemCategory = $itemData['item_category'] ?? $itemData['category'] ?? null;
                    $description = $itemData['description'] ?? $itemData['item_description'] ?? null;
                    $receiverName = $itemData['receiver_name'] ?? $itemData['recipient_name'] ?? null;
                    $receiverPhone = $itemData['receiver_phone'] ?? $itemData['recipient_phone'] ?? null;

                    $createdItems[] = JobItem::create([
                        'job_id' => $job->id,
                        'title' => $itemData['title'],
                        'receiver_name' => $receiverName,
                        'receiver_phone' => $receiverPhone,
                        'item_category' => $itemCategory,
                        'description' => $description,
                        'pickup_address' => $itemData['pickup_address'],
                        'pickup_lat' => $itemData['pickup_lat'] ?? null,
                        'pickup_lng' => $itemData['pickup_lng'] ?? null,
                        'dropoff_lat' => $itemData['dropoff_lat'] ?? null,
                        'dropoff_lng' => $itemData['dropoff_lng'] ?? null,
                        'dropoff_address' => $itemData['dropoff_address'],
                        'mobility_type_needed' => $itemData['mobility_type_needed'] ?? null,
                        'price' => $itemData['price'] ?? $validated['price'] ?? null,
                        'price_type' => $itemData['price_type'] ?? $validated['price_type'] ?? 'fixed',
                        'status' => $itemData['status'] ?? 'open',
                        'posted_at' => now(),
                        'expires_at' => $itemData['expires_at'] ?? $validated['expires_at'] ?? null,
                    ]);
                }

                return $job;
            });

            return response()->json([
                'success' => true,
                'message' => 'Job items created successfully.',
                'job_id' => $job->id,
                'job_count' => 1,
                'data' => $createdItems,
            ], 201);
        } catch (\Throwable $e) {
            Log::error('JobItemController create failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create job',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // SHOW — Get a single job item
    // GET /job-items/{jobItem}
    // =========================================================================
    public function show(JobItem $jobItem): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Job item retrieved successfully.',
            'data'    => $jobItem,
        ], 200);
    }


    // =========================================================================
    // UPDATE — Update a job item
    // PUT /job-items/{jobItem}
    // =========================================================================
    public function update(Request $request, JobItem $jobItem): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'job_id'               => ['prohibited'],
            'status'               => ['prohibited'],
            'posted_at'            => ['prohibited'],
            'title'                => ['sometimes', 'string', 'max:255'],
            'receiver_name'        => ['nullable', 'string', 'max:255'],
            'description'          => ['nullable', 'string', 'max:5000'],
            'pickup_address'       => ['sometimes', 'string', 'max:500'],
            'pickup_lat'           => ['nullable', 'numeric', 'between:-90,90'],
            'pickup_lng'           => ['nullable', 'numeric', 'between:-180,180'],
            'dropoff_address'      => ['sometimes', 'string', 'max:500'],
            'dropoff_lat'          => ['nullable', 'numeric', 'between:-90,90'],
            'dropoff_lng'          => ['nullable', 'numeric', 'between:-180,180'],
            'mobility_type_needed' => ['nullable', 'string', Rule::in(['bike', 'van'])],
            'price'                => ['nullable', 'numeric', 'min:0'],
            'platform_fee'         => ['nullable', 'numeric', 'min:0'],
            'order_fee'            => ['nullable', 'numeric', 'min:0'],
            'total_fair_fee'       => ['nullable', 'numeric', 'min:0'],
            'price_type'           => ['sometimes', 'string', Rule::in(['fixed', 'negotiable'])],
            'expires_at'           => ['nullable', 'date', 'after:now'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $jobItem->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Job item updated successfully.',
            'data'    => $jobItem->fresh(),
        ], 200);
    }

    // =========================================================================
    // DESTROY — Delete a job item
    // DELETE /job-items/{jobItem}
    // =========================================================================
    public function destroy(JobItem $jobItem): JsonResponse
    {
        $jobItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Job item deleted successfully.',
            'data'    => null,
        ], 200);
    }

    // =========================================================================
    // UPDATE STATUS — Change job item status
    // PATCH /job-items/{jobItem}/status
    // =========================================================================
    public function updateStatus(Request $request, JobItem $jobItem): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => [
                'required',
                'string',
                Rule::in(['open', 'matched', 'in_progress', 'completed', 'cancelled'])
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = ['status' => $request->status];

        if ($request->status === 'completed') {
            $data['delivered_at'] = now();
        }

        $jobItem->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Job item status updated successfully.',
            'data'    => $jobItem->fresh(),
        ], 200);
    }
}
