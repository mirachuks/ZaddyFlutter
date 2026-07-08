<?php

namespace App\Http\Controllers\Rider;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RiderProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RiderProfileController extends Controller
{

    // =========================================================================
    // INDEX — List all rider profiles (admin)
    // GET /api/riders
    // Supports filters: ?status=active&service_zone=Lagos&mobility_type=bike
    // =========================================================================
    public function index(Request $request): JsonResponse
    {
        $query = RiderProfile::with('user:id,first_name,last_name,email');

        // ── Filters ──────────────────────────────────────────────────────────
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('service_zone')) {
            $query->where('service_zone', $request->service_zone);
        }
        if ($request->filled('mobility_type')) {
            $query->where('mobility_type', $request->mobility_type);
        }
        if ($request->filled('is_available')) {
            $query->where('is_available', $request->is_available);
        }
        if ($request->filled('state')) {
            $query->where('state', $request->state);
        }
        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }

        // ── Sorting ───────────────────────────────────────────────────────────
        $sortBy       =  $request->get('sort_by', 'created_at');
        $sortOrder    =  $request->get('sort_order', 'desc');
        $allowedSorts =  ['created_at', 'legal_name', 'total_trips', 'review_rank'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        }

        $perPage = min((int) $request->get('per_page', 15), 100);
        $riders  = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $riders,
        ]);
    }

    // =========================================================================
    // STORE — Create a new rider profile
    // POST /api/riders
    // =========================================================================
    // Final rider registration stores guarantor details in rider_profiles.guarantors JSON.
    // Legacy guarantor table writes are not used during this profile completion flow.
    public function store(Request $request): JsonResponse
    {
        if (Auth::check()) {
            $request->merge([
                'user_id' => Auth::id(),
                'legal_name' => trim(Auth::user()->first_name . ' ' . Auth::user()->last_name),
                'mobile_number' => Auth::user()->mobile_number,
            ]);
        }

        $requestData = $request->all();
        $normalizedGuarantors = $this->normalizeGuarantorPayload($requestData);
        if (!empty($normalizedGuarantors)) {
            $requestData['guarantors'] = $normalizedGuarantors;
            $request->merge($requestData);
        }

        $userId = $request->input('user_id') ?? (Auth::check() ? Auth::id() : null);
        $existingProfile = $userId ? RiderProfile::where('user_id', $userId)->first() : null;

        $validatorRules = [
            'user_id'             => [
                'required',
                'integer',
                'exists:users,id',
                $existingProfile
                    ? Rule::unique('rider_profiles', 'user_id')->ignore($existingProfile->id)
                    : Rule::unique('rider_profiles', 'user_id')
            ],
            'first_name'          => ['nullable', 'string', 'max:255'],
            'last_name'           => ['nullable', 'string', 'max:255'],
            'mobile_number'       => [
                'nullable',
                'digits:11',
                $existingProfile
                    ? Rule::unique('rider_profiles', 'mobile_number')->ignore($existingProfile->id)
                    : Rule::unique('rider_profiles', 'mobile_number')
            ],
            'nin'                 => [
                'sometimes',
                'nullable',
                'string',
                'max:11',
                $existingProfile
                    ? Rule::unique('rider_profiles', 'nin')->ignore($existingProfile->id)
                    : Rule::unique('rider_profiles', 'nin')
            ],
            'service_zone'        => ['sometimes', 'nullable', 'string', 'max:255'],
            'gender'              => ['sometimes', 'nullable', 'string', Rule::in(['male', 'female', 'other'])],
            'state'               => ['sometimes', 'nullable', 'string', 'max:100'],
            'mobility_type'       => ['sometimes', 'string', Rule::in(['bike', 'van'])],
            'plate_number'        => [
                'sometimes',
                'string',
                'max:20',
                $existingProfile
                    ? Rule::unique('rider_profiles', 'plate_number')->ignore($existingProfile->id)
                    : Rule::unique('rider_profiles', 'plate_number')
            ],
            'profile_image'       => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'image'               => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'license_number'      => ['nullable', 'string', 'max:255'],
            'license_expiry_date' => ['nullable', 'date'],
            'license_image'       => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'license_back_image'  => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'bike_brand'          => ['nullable', 'string', 'max:255'],
            'bike_model'          => ['nullable', 'string', 'max:255'],
            'bike_production_year' => ['nullable', 'string', 'max:4'],
            'bike_plate_number'   => [
                'nullable',
                'string',
                'max:20',
                $existingProfile
                    ? Rule::unique('rider_profiles', 'plate_number')->ignore($existingProfile->id)
                    : Rule::unique('rider_profiles', 'plate_number')
            ],
            'bike_color'          => ['nullable', 'string', 'max:100'],
            'bike_registration_cert' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'bike_image'          => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'bike_engine_number'  => ['nullable', 'string', 'max:100'],
            'bike_chassis_number' => ['nullable', 'string', 'max:100'],
            'first_name'          => ['sometimes', 'nullable', 'string', 'max:255'],
            'last_name'           => ['sometimes', 'nullable', 'string', 'max:255'],
            'email'               => ['sometimes', 'nullable', 'email', 'max:255'],
            'guarantors'          => ['nullable', 'array', 'max:2'],
            'guarantors.*.name'   => ['nullable', 'string', 'max:255'],
            'guarantors.*.phone'  => ['nullable', 'string', 'digits:11'],
            'guarantors.*.email'  => ['nullable', 'email', 'max:255'],
            'guarantors.*.nin'    => ['required_with:guarantors', 'string', 'max:255'],
            'guarantors.*.id_type' => ['required_with:guarantors', 'string', 'max:100'],
            'guarantors.*.relationship' => ['nullable', 'string', 'max:255'],
            'guarantors.*.state' => ['nullable', 'string', 'max:100'],
            'guarantors.*.address' => ['nullable', 'string', 'max:255'],
            'guarantors.*.nin_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'guarantors.*.id_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ];

        $validator = Validator::make($request->all(), $validatorRules);

        $validator->after(function ($validator) use ($request) {
            $guarantors = $request->input('guarantors', []);
            if (!empty($guarantors) && is_array($guarantors)) {
                foreach ($guarantors as $index => $guarantor) {
                    $idType = strtoupper(trim((string) ($guarantor['id_type'] ?? '')));
                    $nin = trim((string) ($guarantor['nin'] ?? ''));

                    if ($idType === 'NIN' && !preg_match('/^[0-9]{11}$/', $nin)) {
                        $validator->errors()->add(
                            "guarantors.$index.nin",
                            'The guarantor NIN must be exactly 11 digits when the ID type is NIN.'
                        );
                    }
                }
            }
        });

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $data = $validator->validated();

        if (empty($data['guarantors']) && !empty($normalizedGuarantors)) {
            $data['guarantors'] = $normalizedGuarantors;
        }

        if (!empty($data['guarantors']) && is_array($data['guarantors'])) {
            $firstGuarantor = $data['guarantors'][0] ?? [];
            if (empty($data['nin']) && !empty($firstGuarantor['nin'] ?? '')) {
                $data['nin'] = $firstGuarantor['nin'];
            }
        }

        $data['nin'] = $this->normalizeOptionalValue($data['nin'] ?? null);

        if (Auth::check()) {
            $data['user_id'] = Auth::id();
            $data['first_name'] = $data['first_name'] ?? Auth::user()->first_name;
            $data['last_name'] = $data['last_name'] ?? Auth::user()->last_name;
            $data['email'] = $data['email'] ?? Auth::user()->email;
            if (empty($data['legal_name'])) {
                $data['legal_name'] = trim($data['first_name'] . ' ' . $data['last_name']);
            }
            if (empty($data['mobile_number'])) {
                $data['mobile_number'] = Auth::user()->mobile_number;
            }
        }

        if ($request->hasFile('profile_image')) {
            $data['image'] = $request->file('profile_image')
                ->store('rider_profiles', 'public');
        }

        if ($request->hasFile('license_image')) {
            $data['license_image'] = $request->file('license_image')
                ->store('rider_documents', 'public');
        }

        if ($request->hasFile('license_back_image')) {
            $data['license_back_image'] = $request->file('license_back_image')
                ->store('rider_documents', 'public');
        }

        if ($request->hasFile('bike_registration_cert')) {
            $data['bike_registration_cert'] = $request->file('bike_registration_cert')
                ->store('rider_documents', 'public');
        }

        if ($request->hasFile('bike_image')) {
            $data['bike_image'] = $request->file('bike_image')
                ->store('rider_documents', 'public');
        }

        $data['mobility_brand'] = $this->normalizeOptionalValue($data['bike_brand'] ?? null);
        $data['mobility_model'] = $this->normalizeOptionalValue($data['bike_model'] ?? null);
        $data['production_year'] = $this->normalizeOptionalValue($data['bike_production_year'] ?? null);
        $data['plate_number'] = $this->normalizeOptionalValue($data['plate_number'] ?? $data['bike_plate_number'] ?? null);
        $data['bike_plate_number'] = $this->normalizeOptionalValue($data['bike_plate_number'] ?? null);
        $data['service_zone'] = $this->normalizeOptionalValue($data['service_zone'] ?? null);
        $data['gender'] = $this->normalizeOptionalValue($data['gender'] ?? null);
        $data['state'] = $this->normalizeOptionalValue($data['state'] ?? null);
        $data['license_number'] = $this->normalizeOptionalValue($data['license_number'] ?? null);
        $data['license_expiry_date'] = $this->normalizeOptionalValue($data['license_expiry_date'] ?? null);
        $data['bike_brand'] = $this->normalizeOptionalValue($data['bike_brand'] ?? null);
        $data['bike_model'] = $this->normalizeOptionalValue($data['bike_model'] ?? null);
        $data['bike_production_year'] = $this->normalizeOptionalValue($data['bike_production_year'] ?? null);
        $data['bike_plate_number'] = $this->normalizeOptionalValue($data['bike_plate_number'] ?? null);
        $data['bike_color'] = $this->normalizeOptionalValue($data['bike_color'] ?? null);
        $data['bike_engine_number'] = $this->normalizeOptionalValue($data['bike_engine_number'] ?? null);
        $data['bike_chassis_number'] = $this->normalizeOptionalValue($data['bike_chassis_number'] ?? null);
        $data['status']        = 'inactive';
        $data['total_trips']   = '0';
        $data['is_available']  = 'no';

        if (!empty($data['guarantors']) && is_array($data['guarantors'])) {
            foreach ($data['guarantors'] as $index => $guarantorData) {
                $data['guarantors'][$index]['email'] = $this->normalizeOptionalValue($guarantorData['email'] ?? null);
                $data['guarantors'][$index]['id_type'] = $this->normalizeOptionalValue($guarantorData['id_type'] ?? null);
                $data['guarantors'][$index]['relationship'] = $this->normalizeOptionalValue($guarantorData['relationship'] ?? null);
                $data['guarantors'][$index]['state'] = trim((string) ($guarantorData['state'] ?? ''));
                $data['guarantors'][$index]['address'] = trim((string) ($guarantorData['address'] ?? ''));

                $guarantorNinFile = $this->getGuarantorFile($request, $index, 'nin_image');
                if ($guarantorNinFile) {
                    $data['guarantors'][$index]['nin_image'] = $guarantorNinFile->store('guarantor_documents', 'public');
                }

                $guarantorIdFile = $this->getGuarantorFile($request, $index, 'id_image');
                if ($guarantorIdFile) {
                    $data['guarantors'][$index]['id_image'] = $guarantorIdFile->store('guarantor_documents', 'public');
                }
            }

            $data['guarantors'] = array_values($data['guarantors']);
        }

        $riderProfile = $existingProfile ? tap($existingProfile)->fill($data)->save() : RiderProfile::create($data);

        if ($existingProfile) {
            $riderProfile = RiderProfile::find($existingProfile->id);
        }

        return response()->json([
            'success' => true,
            'message' => $existingProfile ? 'Rider profile updated successfully.' : 'Rider profile created successfully.',
            'data'    => $riderProfile->load('user:id,first_name,last_name,email'),
        ], $existingProfile ? 200 : 201);
    }

    protected function normalizeGuarantorPayload(array $requestData): array
    {
        if (!empty($requestData['guarantors']) && is_array($requestData['guarantors'])) {
            return array_values($requestData['guarantors']);
        }

        $normalized = [];
        foreach ($requestData as $key => $value) {
            if (preg_match('/^guarantors\[(\d+)\]\[(.+)\]$/', $key, $matches)) {
                $index = (int) $matches[1];
                $field = $matches[2];
                $normalized[$index][$field] = $value;
            }
        }

        return array_values($normalized);
    }

    protected function normalizeOptionalValue($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            return $trimmed === '' ? null : $trimmed;
        }

        return (string) $value;
    }

    protected function getGuarantorFile(Request $request, int $index, string $field)
    {
        $dotNotation = "guarantors.{$index}.{$field}";
        if ($request->hasFile($dotNotation)) {
            return $request->file($dotNotation);
        }

        foreach ($request->allFiles() as $key => $file) {
            if (
                preg_match('/^guarantors\[(\d+)\]\[(.+)\]$/', $key, $matches)
                && (int) $matches[1] === $index
                && $matches[2] === $field
            ) {
                return $file;
            }
        }

        return null;
    }

    // =========================================================================
    // SHOW — Get a single rider profile
    // GET /api/riders/{riderProfile}
    // =========================================================================
    public function show(RiderProfile $riderProfile): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $riderProfile->load('user:id,first_name,last_name,email'),
        ]);
    }

    // =========================================================================
    // MY PROFILE — Authenticated rider views their own profile
    // GET /api/rider/me
    // =========================================================================
    public function myProfile(Request $request): JsonResponse
    {
        $routeId = $request->route('id');

        $query = RiderProfile::with('user:id,first_name,last_name,email');

        if ($routeId) {
            $query->where('id', $routeId);
        } else {
            $query->where('user_id', $request->user()->id);
        }

        $profile = $query->first();

        if (! $profile) {
            return response()->json([
                'success' => false,
                'message' => 'Rider profile not found for this account.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $profile,
        ]);
    }

    // =========================================================================
    // BY USER — Look up a rider's profile by their user id (e.g. for a
    // customer viewing the profile of a rider who applied to their job).
    // Same field exposure as availableInZone — no ownership check.
    // GET /api/riders/by-user/{userId}
    // =========================================================================
    public function byUser(Request $request, $userId): JsonResponse
    {
        $profile = RiderProfile::with('user:id,first_name,last_name,email')
            ->where('user_id', $userId)
            ->first();

        if (! $profile) {
            return response()->json([
                'success' => false,
                'message' => 'Rider profile not found for this user.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $profile,
        ]);
    }

    // =========================================================================
    // UPDATE — Update rider profile details
    // PUT /api/riders/{riderProfile}
    // =========================================================================
    public function update(Request $request, RiderProfile $riderProfile): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'legal_name'    => ['sometimes', 'string', 'max:255'],
            'mobile_number' => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('rider_profiles', 'mobile_number')
                    ->ignore($riderProfile->id)
            ],
            'service_zone'  => ['sometimes', 'string', 'max:255'],
            'nin'           => [
                'nullable',
                'string',
                'max:11',
                Rule::unique('rider_profiles', 'nin')
                    ->ignore($riderProfile->id)
            ],
            'gender'        => ['sometimes', 'string', Rule::in(['male', 'female', 'other'])],
            'state'         => ['sometimes', 'string', 'max:100'],
            'mobility_type' => ['sometimes', 'string', Rule::in(['bike', 'van', 'bicycle'])],
            'plate_number'  => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('rider_profiles', 'plate_number')
                    ->ignore($riderProfile->id)
            ],
            'image'         => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $data = $validator->validated();

        if ($request->hasFile('image')) {
            if ($riderProfile->image) {
                Storage::disk('public')->delete($riderProfile->image);
            }
            $data['image'] = $request->file('image')
                ->store('rider_profiles', 'public');
        }

        $riderProfile->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Rider profile updated successfully.',
            'data'    => $riderProfile->fresh('user:id,first_name,last_name,email'),
        ]);
    }

    // =========================================================================
    // UPDATE BANK DETAILS — Rider can set bank details; account name must match
    // their profile on first set. Subsequent edits cannot change account_name.
    // PATCH /api/riders/{riderProfile}/bank
    // =========================================================================
    public function updateBankDetails(Request $request, RiderProfile $riderProfile): JsonResponse
    {
        // Ensure ownership
        if ($request->user()->id !== $riderProfile->user_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'bank_account_name' => ['sometimes', 'string', 'max:255'],
            'bank_account_number' => ['required', 'string', 'max:64'],
            'bank_name' => ['required', 'string', 'max:255'],
            'bank_code' => ['nullable', 'string', 'max:32'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $data = $validator->validated();

        // If bank_account_name is set already in DB, disallow changing it
        if (!empty($riderProfile->bank_account_name)) {
            if (!empty($data['bank_account_name']) && $data['bank_account_name'] !== $riderProfile->bank_account_name) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account name cannot be changed after initial setup. Contact support to change it.',
                ], 403);
            }
        } else {
            // First time set — ensure name matches user profile
            $expected = trim(($riderProfile->first_name ?? '') . ' ' . ($riderProfile->last_name ?? ''));
            $provided = trim($data['bank_account_name'] ?? '');
            if ($provided === '' || strcasecmp($provided, $expected) !== 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bank account name must match your profile name exactly on initial setup.',
                ], 422);
            }
        }

        // Persist allowed fields
        $riderProfile->bank_account_name = $riderProfile->bank_account_name ?? ($data['bank_account_name'] ?? null);
        $riderProfile->bank_account_number = $data['bank_account_number'];
        $riderProfile->bank_name = $data['bank_name'];
        $riderProfile->bank_code = $data['bank_code'] ?? $riderProfile->bank_code;
        $riderProfile->save();

        return response()->json([
            'success' => true,
            'message' => 'Bank details updated.',
            'data' => $riderProfile->fresh(),
        ]);
    }


    // =========================================================================
    // UPDATE LOCATION — Rider pings current GPS coordinates
    // PATCH /api/riders/{riderProfile}/location
    // =========================================================================
    public function updateLocation(Request $request, RiderProfile $riderProfile): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_latitude'  => ['required', 'numeric', 'between:-90,90'],
            'current_longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $riderProfile->update([
            'current_latitude' => $request->current_latitude,
            'current_longitude' => $request->current_longitude,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Location updated.',
            'data' => $riderProfile->fresh(),
        ]);
    }


    // =========================================================================
    // TOGGLE AVAILABILITY — Rider goes online / offline
    // PATCH /api/riders/{riderProfile}/availability
    // =========================================================================
    public function toggleAvailability(Request $request, RiderProfile $riderProfile): JsonResponse
    {
        $user = $riderProfile->user()->first();

        if ($riderProfile->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Only active riders can change their availability.',
            ], 403);
        }

        $isVerified = (string) ($user->is_verified ?? '') === 'yes' || (int) ($user->is_verified ?? 0) === 1;
        if (!$isVerified) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is still awaiting verification confirmation. You will be able to go online after verification is approved.',
                'requires_verification' => true,
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'current_latitude'  => ['required', 'numeric', 'between:-90,90'],
            'current_longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $newAvailability = $riderProfile->is_available === 'yes' ? 'no' : 'yes';

        $riderProfile->update([
            'is_available'      => $newAvailability,
            'current_latitude'  => $request->current_latitude,
            'current_longitude' => $request->current_longitude,
        ]);

        return response()->json([
            'success'      => true,
            'message'      => 'Availability updated.',
            'is_available' => $newAvailability,
            'data'         => $riderProfile->fresh(),
        ], 200);
    }

    // =========================================================================
    // CHANGE STATUS — Admin sets active / inactive / suspended / banned
    // PATCH /api/riders/{riderProfile}/status
    // =========================================================================
    public function changeStatus(Request $request, RiderProfile $riderProfile): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => [
                'required',
                'string',
                Rule::in(['active', 'inactive', 'suspended', 'banned'])
            ],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $oldStatus  = $riderProfile->status;
        $newStatus  = $request->status;
        $updateData = ['status' => $newStatus];

        // Force offline when deactivating/suspending/banning
        if (in_array($newStatus, ['inactive', 'suspended', 'banned'])) {
            $updateData['is_available'] = 'no';
        }

        $riderProfile->update($updateData);

        return response()->json([
            'success' => true,
            'message' => "Rider status changed from [{$oldStatus}] to [{$newStatus}].",
            'data'    => $riderProfile->fresh(),
        ]);
    }


    // =========================================================================
    // INCREMENT TRIPS — Called server-side after a trip completes
    // PATCH /api/riders/{riderProfile}/trips/increment
    // =========================================================================
    public static function incrementTrips(RiderProfile $riderProfile): JsonResponse
    {
        $riderProfile->increment('total_trips');

        return response()->json([
            'success'     => true,
            'message'     => 'Trip count incremented.',
            'total_trips' => $riderProfile->fresh()->total_trips,
        ]);
    }


    // =========================================================================
    // UPDATE REVIEW RANK — Update rider rating after a trip
    // PATCH /api/riders/{riderProfile}/review-rank
    // =========================================================================
    public function updateReviewRank(Request $request, RiderProfile $riderProfile): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'review_rank' => ['required', 'numeric', 'min:0', 'max:5'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $riderProfile->update(['review_rank' => $request->review_rank]);

        return response()->json([
            'success'     => true,
            'message'     => 'Review rank updated.',
            'review_rank' => $riderProfile->review_rank,
        ]);
    }


    // =========================================================================
    // AVAILABLE IN ZONE — Find available riders for dispatch
    // GET /api/riders/available?service_zone=Enugu&mobility_type=bike
    // =========================================================================
    public function availableInZone(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'service_zone'  => ['required', 'string'],
            'mobility_type' => ['nullable', 'string', Rule::in(['bike', 'van'])],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $query = RiderProfile::with('user:id,first_name,last_name,email')
            ->where('service_zone', $request->service_zone)
            ->where('status', 'active')
            ->where('is_available', 'yes');

        if ($request->filled('mobility_type')) {
            $query->where('mobility_type', $request->mobility_type);
        }

        // Best-ranked riders first
        $riders = $query->orderByDesc('review_rank')->get();

        return response()->json([
            'success' => true,
            'count'   => $riders->count(),
            'data'    => $riders,
        ]);
    }


    // =========================================================================
    // DESTROY — Hard-delete a rider profile (admin)
    // DELETE /api/riders/{riderProfile}
    // =========================================================================
    public function destroy(RiderProfile $riderProfile): JsonResponse
    {
        if ($riderProfile->image) {
            Storage::disk('public')->delete($riderProfile->image);
        }

        $riderProfile->delete();

        return response()->json([
            'success' => true,
            'message' => 'Rider profile deleted successfully.',
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
}
