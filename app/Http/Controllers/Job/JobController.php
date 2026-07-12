<?php

namespace App\Http\Controllers\Job;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AppNotification;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;
use App\Models\EscrowTransaction;
use App\Models\UserWallet;
use App\Models\RiderProfile;
use App\Http\Controllers\Wallet\UserWalletController;
use App\Http\Controllers\Job\JobItemController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class JobController extends Controller
{
    // =====================================================================
    // INDEX — List all jobs (public with filters)
    // GET /api/jobs
    // Filters: ?status=open&mobility_type_needed=bike&price_type=fixed
    //          &pickup_address=Lagos&search=delivery&expires_after=2025-01-01
    // =========================================================================
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

        // Only return jobs that haven't expired
        if ($request->boolean('active_only', false)) {
            $query->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
        }

        if ($request->filled('expires_after')) {
            $query->where('expires_at', '>', $request->expires_after);
        }

        // Price range filter
        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Sorting
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



    public function store(Request $request)
    {
        // If the request contains item details, keep the batch item flow.
        if (($request->filled('items') && is_array($request->input('items'))) ||
            ($request->filled('parcels') && is_array($request->input('parcels')))
        ) {
            $jobItemController = new JobItemController();
            return $jobItemController->create($request);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'user_profile_id' => ['nullable', 'integer', 'exists:users,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'pickup_address' => ['nullable', 'string', 'max:500'],
            'pickup_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'pickup_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'dropoff_address' => ['nullable', 'string', 'max:500'],
            'dropoff_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'dropoff_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'mobility_type_needed' => ['nullable', 'string', Rule::in(['bike', 'van', 'truck', 'car'])],
            'price' => ['nullable', 'numeric', 'min:0'],
            'estimated_fare' => ['nullable', 'numeric', 'min:0'],
            'platform_charge' => ['nullable', 'numeric', 'min:0'],
            'order_fee' => ['nullable', 'numeric', 'min:0'],
            'total_price' => ['nullable', 'numeric', 'min:0'],
            'price_type' => ['nullable', 'string', Rule::in(['fixed', 'negotiable'])],
            'status' => ['nullable', 'string', Rule::in(['open', 'matched', 'in_progress', 'completed', 'cancelled'])],
            'payment_status' => ['nullable', 'string', Rule::in(['pending', 'paid', 'refunded'])],
            'posted_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $validated = $validator->validated();

        $jobAttributes = [
            'user_id' => $validated['user_id'] ?? $validated['user_profile_id'] ?? ($request->user()?->id ?? 1),
            'title' => $validated['title'] ?? null,
            'description' => $validated['description'] ?? null,
            'pickup_address' => $validated['pickup_address'] ?? null,
            'pickup_lat' => $validated['pickup_lat'] ?? null,
            'pickup_lng' => $validated['pickup_lng'] ?? null,
            'dropoff_address' => $validated['dropoff_address'] ?? null,
            'dropoff_lat' => $validated['dropoff_lat'] ?? null,
            'dropoff_lng' => $validated['dropoff_lng'] ?? null,
            'mobility_type_needed' => $validated['mobility_type_needed'] ?? null,
            'price' => $validated['price'] ?? $validated['estimated_fare'] ?? null,
            'platform_charge' => $validated['platform_charge'] ?? null,
            'price_type' => $validated['price_type'] ?? 'fixed',
            'status' => $validated['status'] ?? 'open',
            'payment_status' => $validated['payment_status'] ?? 'pending',
            'posted_at' => $validated['posted_at'] ?? now(),
            'expires_at' => $validated['expires_at'] ?? null,
        ];

        if (Schema::hasColumn('jobs', 'order_fee')) {
            $jobAttributes['order_fee'] = $validated['order_fee'] ?? null;
        }

        if (Schema::hasColumn('jobs', 'total_price')) {
            $jobAttributes['total_price'] = $validated['total_price'] ?? null;
        }

        $job = Job::create($jobAttributes);

        return response()->json([
            'success' => true,
            'message' => 'Job created successfully.',
            'job_id' => $job->id,
            'data' => $job,
        ], 201);
    }


    public function show(Job $job): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $job->load([
                'user:id,first_name,last_name,email,mobile_number',
                'applications.userRider:id,first_name,last_name,email,mobile_number',
                'items',
            ]),
        ]);
    }

    /**
     * Get available jobs for riders near a given location.
     * GET /api/jobs/available?latitude=6.5244&longitude=3.3792&radius=10
     */
    public function available(Request $request): JsonResponse
    {
        $latitude = $request->float('latitude');
        $longitude = $request->float('longitude');
        $radius = max(1, (int) $request->get('radius', 10));

        $query = Job::query()
            ->where('status', 'open')
            ->with(['user:id,first_name,last_name,email,mobile_number', 'items'])
            ->orderByDesc('created_at');

        $driverName = DB::connection()->getDriverName();

        // For MySQL, use distance-based filtering
        // For SQLite/testing, just return all open jobs
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
        $jobs = $jobs->map(function ($job) {
            $jobArray = $job->toArray();
            $jobArray['customer'] = $jobArray['user'] ?? null;
            return $jobArray;
        });

        return response()->json([
            'success' => true,
            'driver' => $driverName,
            'count' => $jobs->count(),
            'data' => $jobs,
        ], 200);
    }


    /**
     * Get all jobs for a specific user ID.
     */
    public function myJobs(Request $request, $userId)
    {
        // Get pagination parameters from query string
        $page = $request->query('page', 1);
        $limit = $request->query('limit', 20);

        // Query the Job model directly using the user_id column
        $jobs = Job::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->with(['user', 'items'])
            ->paginate($limit, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => $jobs->items(),
            'pagination' => [
                'total' => $jobs->total(),
                'current_page' => $jobs->currentPage(),
                'per_page' => $jobs->perPage(),
                'last_page' => $jobs->lastPage(),
            ]
        ]);
    }
    // =========================================================================
    // UPDATE — Edit a job (only while open)
    // PUT /api/jobs/{job}
    // =========================================================================
    public function update(Request $request, Job $job): JsonResponse
    {
        if (! $this->canManageJob($request, $job)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorised to edit this job.',
            ], 403);
        }

        // Can only edit open jobs
        if ($job->status !== 'open') {
            return response()->json([
                'success' => false,
                'message' => 'Only open jobs can be edited.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'title'                => ['sometimes', 'string', 'max:255'],
            'description'          => ['nullable', 'string', 'max:5000'],
            'pickup_address'       => ['sometimes', 'string', 'max:500'],
            'pickup_lat'           => ['nullable', 'numeric', 'between:-90,90'],
            'pickup_lng'           => ['nullable', 'numeric', 'between:-180,180'],
            'dropoff_address'      => ['sometimes', 'string', 'max:500'],
            'mobility_type_needed' => [
                'nullable',
                'string',
                Rule::in(['bike', 'van'])
            ],
            'price'                => ['nullable', 'numeric', 'min:0'],
            'price_type'           => [
                'sometimes',
                'string',
                Rule::in(['fixed', 'negotiable'])
            ],
            'expires_at'           => ['nullable', 'date', 'after:now'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $job->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Job updated successfully.',
            'data'    => $job->fresh('user:id,name,email,mobile_number'),
        ]);
    }

    // =========================================================================
    // CHANGE STATUS — Move job through its lifecycle
    // PATCH /api/jobs/{job}/status
    // =========================================================================
    public function changeStatus(Request $request, Job $job): JsonResponse
    {
        if (! $this->canManageJob($request, $job)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorised to change the status of this job.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => [
                'required',
                'string',
                Rule::in(['open', 'matched', 'accepted', 'in_progress', 'completed', 'cancelled', 'picked_up', 'delivered'])
            ],
            'payment_status' => ['sometimes', 'string', Rule::in(['pending', 'paid', 'refunded'])],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        // Enforce valid status transitions
        $transitions = [
            'open'        => ['matched', 'accepted', 'cancelled'],
            'matched'     => ['accepted', 'in_progress', 'open', 'cancelled'],
            'accepted'    => ['picked_up', 'in_progress', 'cancelled'],
            'picked_up'   => ['delivered', 'in_progress', 'cancelled'],
            'in_progress' => ['completed', 'delivered', 'cancelled'],
            'delivered'   => ['completed', 'cancelled'],
            'completed'   => [],   // terminal — no further transitions
            'cancelled'   => [],   // terminal — no further transitions
        ];

        $currentStatus    = $job->status;
        $allowedNext      = $transitions[$currentStatus] ?? [];

        if (! in_array($request->status, $allowedNext)) {
            return response()->json([
                'success' => false,
                'message' => "Cannot transition job from [{$currentStatus}] to [{$request->status}].",
                'allowed' => $allowedNext,
            ], 422);
        }

        $updateData = ['status' => $request->status];

        // Only allow transitioning from 'accepted' to 'in_progress' when payment
        // has been confirmed, except when the payment method is the user's wallet.
        if ($request->status === 'in_progress' && $job->status === 'accepted') {
            $escrow = EscrowTransaction::where('job_id', $job->id)->latest()->first();
            $isWalletPayment = $escrow && ($escrow->payment_method === 'wallet' || $escrow->payment_method === 'user_wallet');

            if (! $isWalletPayment && $job->payment_status !== 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot start job: payment not confirmed. Await admin approval or complete payment.',
                ], 422);
            }

            // For wallet payments we allow starting the job without auto-marking
            // the job's `payment_status` to 'paid'. The wallet debit flow will
            // update balances and payout the rider but the payment_status field
            // remains under the payment/release flow control.
        }

        // Stamp delivered_at when job is marked completed or delivered
        if (in_array($request->status, ['completed', 'delivered'])) {
            $updateData['delivered_at'] = now();
        }

        if ($request->filled('payment_status')) {
            $updateData['payment_status'] = $request->payment_status;
        }

        $job->update($updateData);

        if (in_array($request->status, ['in_progress', 'delivered', 'completed'], true)) {
            $acceptedApplication = $job->applications()
                ->whereIn('status', ['accepted', 'in_progress', 'delivered'])
                ->first();

            if ($acceptedApplication) {
                $acceptedApplication->update(['status' => $request->status]);
            }
        }

        AppNotification::create([
            'user_id' => $job->user_id,
            'type' => 'job_status_changed',
            'payload' => [
                'title' => 'Job Status Updated',
                'body' => "Your job \"{$job->title}\" is now {$request->status}.",
            ],
            'is_read' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Job status changed from [{$currentStatus}] to [{$request->status}].",
            'data'    => $job->fresh(),
        ]);
    }

    // =========================================================================
    // ACCEPT JOB — Rider accepts a job directly (changes status to accepted)
    // PATCH /api/jobs/{job}/accept
    // =========================================================================
    public function acceptJob(Request $request, Job $job): JsonResponse
    {
        try {
            $rider = $request->user();

            // Verify rider is authenticated
            if (!$rider) {
                return response()->json([
                    'success' => false,
                    'message' => 'You must be logged in to accept a job.',
                ], 401);
            }

            // Job must be in "open" status to be accepted
            if ($job->status !== 'open') {
                return response()->json([
                    'success' => false,
                    'message' => "This job cannot be accepted. Current status: {$job->status}",
                ], 422);
            }

            // Rider cannot accept their own job
            if ($job->user_id === $rider->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot accept your own job.',
                ], 403);
            }

            // Update job status to "accepted" and mark payment pending.
            $job->update([
                'status' => 'accepted',
                'payment_status' => 'pending',
            ]);

            // Create or update job application for the rider
            $application = JobApplication::updateOrCreate(
                [
                    'job_id' => $job->id,
                    'user_rider_id' => $rider->id,
                ],
                [
                    'status' => 'accepted',
                    'msg' => 'Job accepted directly by rider',
                ]
            );

            // Notify the job owner to proceed to payment immediately.
            AppNotification::create([
                'user_id' => $job->user_id,
                'type' => 'rider_accepted',
                'payload' => [
                    'title' => 'Rider Accepted Your Job',
                    'body' => "A rider accepted your job \"{$job->title}\". Please complete payment.",
                    'action' => 'redirect_to_payment',
                    'job_id' => $job->id,
                ],
                'is_read' => false,
            ]);

            // Fetch fresh job data with attached items so the rider screens
            // receive the full parcel breakdown immediately after acceptance.
            $updatedJob = $job->fresh();
            $updatedJob->load('items');

            return response()->json([
                'success' => true,
                'message' => 'Job accepted successfully.',
                'data' => $updatedJob,
            ]);
        } catch (\Throwable $e) {
            \Log::error('acceptJob error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to accept job: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // MARK DELIVERED — Shorthand to complete a job and stamp delivered_at
    // PATCH /api/jobs/{job}/deliver
    // =========================================================================
    public function markDelivered(Request $request, Job $job): JsonResponse
    {
        if (! $this->canManageJob($request, $job)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorised to mark this job as delivered.',
            ], 403);
        }

        if ($job->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'message' => 'Only in-progress jobs can be marked as delivered.',
            ], 422);
        }

        $job->update([
            'status'       => 'delivered',
            'delivered_at' => now(),
        ]);

        $notifyUserIds = [$job->user_id];

        // Ensure the job's rider application is also marked delivered so
        // the front-end sees a consistent `delivered` state before
        // the customer confirms to `completed`.
        $riderApplication = $job->riderApplication ?? $job->acceptedApplication;
        if ($riderApplication) {
            if (! in_array($riderApplication->status, ['delivered'], true)) {
                $riderApplication->update(['status' => 'delivered']);
            }

            $notifyUserIds[] = $riderApplication->user_rider_id;
        }

        // If a manual payment or escrow was created before the rider was assigned,
        // and admin approved the payment (held status), we may need to credit
        // the rider now that the job has been delivered.
        try {
            $escrowTransaction = EscrowTransaction::where('job_id', $job->id)
                ->whereIn('status', [EscrowTransaction::STATUS_PENDING, EscrowTransaction::STATUS_HELD])
                ->first();

            if ($escrowTransaction && $riderApplication) {
                // Ensure rider_profile_id is set on the transaction
                if (! $escrowTransaction->rider_profile_id) {
                    $rpId = RiderProfile::where('user_id', $riderApplication->user_rider_id)->value('id');
                    if ($rpId) {
                        $escrowTransaction->rider_profile_id = $rpId;
                    }
                }

                $amountToCredit = max(0, ($escrowTransaction->balance ?? 0) - ($escrowTransaction->platform_fee ?? 0));

                // If transaction already held, credit the rider now (idempotent check)
                if ($escrowTransaction->status === EscrowTransaction::STATUS_HELD && $amountToCredit > 0) {
                    // Prevent double-credit by checking rider_payout
                    if (empty($escrowTransaction->rider_payout) || $escrowTransaction->rider_payout != $amountToCredit) {
                        $escrowTransaction->rider_payout = $amountToCredit;
                        $escrowTransaction->save();

                        // Credit rider wallet
                        UserWalletController::ensureWallet($riderApplication->user_rider_id);
                        UserWalletController::credit([
                            'user_id' => $riderApplication->user_rider_id,
                            'amount' => $amountToCredit,
                            'purpose' => 'job_earnings',
                        ]);
                    }
                }

                // Persist any rider_profile_id changes
                if ($escrowTransaction->isDirty()) {
                    $escrowTransaction->save();
                }
            }
        } catch (\Throwable $e) {
            \Log::error('Failed to process escrow on delivery: ' . $e->getMessage(), ['job_id' => $job->id]);
        }

        foreach (array_unique($notifyUserIds) as $notifyUserId) {
            AppNotification::create([
                'user_id' => $notifyUserId,
                'type' => 'job_delivered',
                'payload' => [
                    'title' => 'Job Delivered',
                    'body' => "Job \"{$job->title}\" has been marked as delivered.",
                ],
                'is_read' => false,
            ]);

            try {
                $user = \App\Models\User::find($notifyUserId);
                if ($user) {
                    $user->notify(new \App\Notifications\JobStatusNotification(
                        'Job Delivered',
                        "Job \"{$job->title}\" has been marked as delivered."
                    ));
                }
            } catch (\Throwable $e) {
                // Log and continue
                \Log::error('Failed to send job delivered notification: ' . $e->getMessage());
            }
        }

        return response()->json([
            'success'      => true,
            'message'      => 'Job marked as delivered.',
            'delivered_at' => $job->delivered_at,
            'data'         => $job->fresh(),
        ]);
    }

    /**
     * AUTO-CONFIRM — Customer did not confirm receipt, admin notified
     * PATCH /api/jobs/{job}/auto-confirm
     */
    public function autoConfirm(Request $request, Job $job): JsonResponse
    {
        if (! $this->canManageJob($request, $job)) {
            // Allow the job owner to trigger auto-confirm (internal) or admins
            $user = $request->user();
            if (! $user || ! $user->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorised to auto-confirm this job.',
                ], 403);
            }
        }

        if ($job->status !== 'delivered') {
            return response()->json([
                'success' => false,
                'message' => 'Only delivered jobs can be auto-confirmed.',
            ], 422);
        }

        $job->update([
            'status' => 'completed',
            'delivered_at' => $job->delivered_at ?? now(),
        ]);

        // Notify customer and rider
        $notifyUserIds = [$job->user_id];
        if ($job->acceptedApplication) {
            $notifyUserIds[] = $job->acceptedApplication->user_rider_id;
        }

        // Notify all admins
        $adminIds = \App\Models\User::where('user_type', 'admin')
            ->orWhere('user_level_id', 7)
            ->pluck('id')
            ->toArray();

        $notifyUserIds = array_unique(array_merge($notifyUserIds, $adminIds));

        foreach ($notifyUserIds as $notifyUserId) {
            AppNotification::create([
                'user_id' => $notifyUserId,
                'type' => 'job_auto_confirmed',
                'payload' => [
                    'title' => 'Job Auto-Confirmed',
                    'body' => "Job \"{$job->title}\" was automatically confirmed (no customer response).",
                ],
                'is_read' => false,
            ]);

            try {
                $user = \App\Models\User::find($notifyUserId);
                if ($user) {
                    $user->notify(new \App\Notifications\JobStatusNotification(
                        'Job Auto-Confirmed',
                        "Job \"{$job->title}\" was automatically confirmed (no customer response)."
                    ));
                }
            } catch (\Throwable $e) {
                \Log::error('Failed to send auto-confirm notification: ' . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Job automatically confirmed and admins notified.',
            'data' => $job->fresh(),
        ]);
    }

    // =========================================================================
    // CANCEL — Owner cancels an open or matched job
    // PATCH /api/jobs/{job}/cancel
    // =========================================================================
    public function cancel(Request $request, Job $job)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required.',
            ], 401);
        }

        $acceptedApplication = $job->acceptedApplication;
        $isOwner = (int) $job->user_id === (int) $user->id;
        $isAdmin = $user->hasRole('admin');
        $isAcceptedRider = $acceptedApplication && ((int) $acceptedApplication->user_rider_id === (int) $user->id);

        if (! $isOwner && ! $isAcceptedRider && ! $isAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorised to cancel this job.',
            ], 403);
        }

        if (in_array($job->status, ['completed', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => "A [{$job->status}] job cannot be cancelled.",
            ], 422);
        }

        $notifyUserIds = [$job->user_id];

        DB::beginTransaction();

        try {
            if ($isAcceptedRider) {
                $applicationToCancel = JobApplication::where('job_id', $job->id)
                    ->where('user_rider_id', $user->id)
                    ->whereIn('status', ['accepted', 'matched', 'pending'])
                    ->latest()
                    ->first();

                if ($applicationToCancel) {
                    $applicationToCancel->update(['status' => 'cancelled']);
                    $notifyUserIds[] = $applicationToCancel->user_rider_id;
                } elseif ($acceptedApplication) {
                    $acceptedApplication->update(['status' => 'cancelled']);
                    $notifyUserIds[] = $acceptedApplication->user_rider_id;
                }

                $job->update([
                    'status' => 'open',
                    'payment_status' => 'pending',
                ]);
            } else {
                if ($acceptedApplication) {
                    $acceptedApplication->update(['status' => 'cancelled']);
                    $notifyUserIds[] = $acceptedApplication->user_rider_id;
                }

                $job->update(['status' => 'cancelled']);

                // Handle payment refund logic when customer cancels
                $this->handleJobCancellationRefund($job);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Job cancel failed: ' . $e->getMessage(), [
                'job_id' => $job->id,
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel job: ' . $e->getMessage(),
            ], 500);
        }

        foreach (array_unique($notifyUserIds) as $notifyUserId) {
            AppNotification::create([
                'user_id' => $notifyUserId,
                'type' => 'job_cancelled',
                'payload' => [
                    'title' => 'Job Cancelled',
                    'body' => "Job \"{$job->title}\" was cancelled.",
                ],
                'is_read' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Job cancelled successfully.',
            'data'    => $job->fresh(),
        ]);
    }

    /**
     * Handle refund logic when a job with accepted rider is cancelled
     * 1. Deduct rider's wallet for the allocated amount
     * 2. Refund customer's wallet
     * 3. Update escrow transaction
     */
    private function handleJobCancellationRefund(Job $job): void
    {
        $escrowTransaction = EscrowTransaction::where('job_id', $job->id)
            ->where('status', EscrowTransaction::STATUS_HELD)
            ->first();

        if (!$escrowTransaction || $escrowTransaction->refund_issued) {
            return; // No transaction or already refunded
        }

        try {
            // Calculate fair price (amount allocated to rider)
            $fairPrice = $job->total_price - ($escrowTransaction->platform_fee ?? 0);

            // 1. Deduct from rider's wallet
            if ($job->rider_id) {
                $riderWallet = UserWallet::where('user_id', $job->rider_id)->first();
                if ($riderWallet && $riderWallet->balance >= $fairPrice) {
                    $riderWallet->decrement('balance', $fairPrice);

                    \App\Models\WalletTransaction::create([
                        'user_id' => $job->rider_id,
                        'transaction_type' => 'debit',
                        'amount' => $fairPrice,
                        'purpose' => 'job_cancellation',
                        'reference_id' => $escrowTransaction->id,
                        'balance_before' => $riderWallet->balance + $fairPrice,
                        'balance_after' => $riderWallet->balance,
                    ]);
                }
            }

            // 2. Refund customer's wallet
            $customerWallet = UserWallet::where('user_id', $job->user_id)->first();
            if ($customerWallet) {
                $refundAmount = $escrowTransaction->balance;
                $customerWallet->increment('balance', $refundAmount);

                \App\Models\WalletTransaction::create([
                    'user_id' => $job->user_id,
                    'transaction_type' => 'credit',
                    'amount' => $refundAmount,
                    'purpose' => 'job_cancellation_refund',
                    'reference_id' => $escrowTransaction->id,
                    'balance_before' => $customerWallet->balance - $refundAmount,
                    'balance_after' => $customerWallet->balance,
                ]);
            }

            // 3. Mark as refunded in escrow transaction
            $escrowTransaction->update([
                'status' => EscrowTransaction::STATUS_REFUNDED,
                'refund_issued' => true,
                'refund_issued_at' => now(),
            ]);

            \Log::info('Job cancellation refund processed', [
                'job_id' => $job->id,
                'rider_id' => $job->rider_id,
                'customer_id' => $job->user_id,
                'fair_price' => $fairPrice,
                'refund_amount' => $escrowTransaction->balance,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Job cancellation refund failed', [
                'job_id' => $job->id,
                'error' => $e->getMessage(),
            ]);
        }
    }


    // =========================================================================
    // EXTEND EXPIRY — Push the expiry date forward
    // PATCH /api/jobs/{job}/extend
    // =========================================================================
    public function extendExpiry(Request $request, Job $job): JsonResponse
    {
        if (! $this->canManageJob($request, $job)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorised to extend this job.',
            ], 403);
        }

        if ($job->status !== 'open') {
            return response()->json([
                'success' => false,
                'message' => 'Only open jobs can have their expiry extended.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'expires_at' => ['required', 'date', 'after:now'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $job->update(['expires_at' => $request->expires_at]);

        return response()->json([
            'success'    => true,
            'message'    => 'Job expiry date extended.',
            'expires_at' => $job->expires_at,
        ]);
    }

    // =========================================================================
    // DESTROY — Hard-delete a job (admin only)
    // DELETE /api/jobs/{job}
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
    // PRIVATE HELPERS
    // =========================================================================
    private function validationError($errors): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors'  => $errors,
        ], 422);
    }

    private function canManageJob(Request $request, Job $job): bool
    {
        $user = $request->user();

        if (!$user) {
            return false;
        }

        // Admin or job poster can manage
        if ($user->hasRole('admin') || (int) $job->user_id === (int) $user->id) {
            return true;
        }

        // Also allow the accepted/in_progress/delivered rider to manage/update job status
        $riderApplication = $job->applications()
            ->where('user_rider_id', $user->id)
            ->whereIn('status', ['accepted', 'in_progress', 'delivered'])
            ->exists();

        return $riderApplication;
    }
}
