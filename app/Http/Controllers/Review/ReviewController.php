<?php

namespace App\Http\Controllers\Review;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Review;
use App\Models\RiderProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ReviewController extends Controller
{
     // =========================================================================
    // INDEX — List all reviews (admin)
    // GET /api/reviews
    // Filters: ?rider_profile_id=3&user_id=5&min_score=3&max_score=5
    // =========================================================================
    public function index(Request $request): JsonResponse
    {
        $query = Review::with([
            'user:id,name,email',
            'riderProfile:id,legal_name,mobile_number',
        ]);

        if ($request->filled('rider_profile_id')) {
            $query->where('rider_profile_id', $request->rider_profile_id);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('min_score')) {
            $query->where('score', '>=', $request->min_score);
        }

        if ($request->filled('max_score')) {
            $query->where('score', '<=', $request->max_score);
        }

        if ($request->filled('search')) {
            $query->where('review', 'like', '%' . $request->search . '%');
        }

        $perPage = min((int) $request->get('per_page', 15), 100);
        $reviews = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $reviews,
        ]);
    }

    // =========================================================================
    // STORE — Submit a review for a rider
    // POST /api/rider-profiles/{riderProfile}/reviews
    // =========================================================================
    public function store(Request $request, RiderProfile $riderProfile): JsonResponse
    {
        // A user can only review a rider once
        $alreadyReviewed = Review::where('user_id', $request->user()->id)
                                 ->where('rider_profile_id', $riderProfile->id)
                                 ->exists();

        if ($alreadyReviewed) {
            return response()->json([
                'success' => false,
                'message' => 'You have already submitted a review for this rider.',
            ], 422);
        }

        // A user cannot review themselves
        if ($riderProfile->user_id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot review your own rider profile.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'review' => ['required', 'string', 'min:10', 'max:1000'],
            'score'  => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $review = Review::create([
            'user_id'          => $request->user()->id,
            'rider_profile_id' => $riderProfile->id,
            'review'           => $request->review,
            'score'            => $request->score,
        ]);

        // Recalculate and update rider's average review_rank
        $this->updateRiderReviewRank($riderProfile);

        return response()->json([
            'success' => true,
            'message' => 'Review submitted successfully.',
            'data'    => $review->load([
                'user:id,name,email',
                'riderProfile:id,legal_name',
            ]),
        ], 201);
    }

    // =========================================================================
    // SHOW — View a single review
    // GET /api/reviews/{review}
    // =========================================================================
    public function show(Review $review): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $review->load([
                'user:id,name,email',
                'riderProfile:id,legal_name,mobile_number',
            ]),
        ]);
    }

    // =========================================================================
    // BY RIDER — All reviews for a specific rider (public)
    // GET /api/rider-profiles/{riderProfile}/reviews
    // =========================================================================
    public function byRider(Request $request, RiderProfile $riderProfile): JsonResponse
    {
        $query = $riderProfile->reviews()
                              ->with('user:id,name,email')
                              ->latest();

        if ($request->filled('min_score')) {
            $query->where('score', '>=', $request->min_score);
        }

        $perPage = min((int) $request->get('per_page', 10), 50);
        $reviews = $query->paginate($perPage);

        // Score summary for the rider
        $summary = [
            'average_score' => round($riderProfile->reviews()->avg('score'), 1),
            'total_reviews' => $riderProfile->reviews()->count(),
            'score_breakdown' => Review::where('rider_profile_id', $riderProfile->id)
                ->selectRaw('score, COUNT(*) as count')
                ->groupBy('score')
                ->orderByDesc('score')
                ->pluck('count', 'score'),
        ];

        return response()->json([
            'success' => true,
            'summary' => $summary,
            'data'    => $reviews,
        ]);
    }

    // =========================================================================
    // MY REVIEWS — All reviews submitted by the authenticated user
    // GET /api/reviews/mine
    // =========================================================================
    public function myReviews(Request $request): JsonResponse
    {
        $reviews = Review::with('riderProfile:id,legal_name,mobile_number')
                         ->where('user_id', $request->user()->id)
                         ->latest()
                         ->paginate(15);

        return response()->json([
            'success' => true,
            'data'    => $reviews,
        ]);
    }

    // =========================================================================
    // UPDATE — Edit own review (only the review text, not the score)
    // PUT /api/reviews/{review}
    // =========================================================================
    public function update(Request $request, Review $review): JsonResponse
    {
        // Only the original reviewer can edit
        if ($review->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only edit your own reviews.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'review' => ['required', 'string', 'min:10', 'max:1000'],
            'score'  => ['sometimes', 'integer', 'min:1', 'max:5'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $review->update($validator->validated());

        // Recalculate rider's average if score was changed
        if ($request->filled('score')) {
            $this->updateRiderReviewRank($review->riderProfile);
        }

        return response()->json([
            'success' => true,
            'message' => 'Review updated successfully.',
            'data'    => $review->fresh([
                'user:id,name,email',
                'riderProfile:id,legal_name',
            ]),
        ]);
    }

    // =========================================================================
    // DESTROY — Delete a review (admin or review owner)
    // DELETE /api/reviews/{review}
    // =========================================================================
    public function destroy(Request $request, Review $review): JsonResponse
    {
        $isAdmin       = $request->user()->hasRole('admin');
        $isOwner       = $review->user_id === $request->user()->id;

        if (! $isAdmin && ! $isOwner) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorised to delete this review.',
            ], 403);
        }

        $riderProfile = $review->riderProfile;

        $review->delete();

        // Recalculate rider's average after deletion
        $this->updateRiderReviewRank($riderProfile);

        return response()->json([
            'success' => true,
            'message' => 'Review deleted successfully.',
        ]);
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Recalculate and persist the rider's average score
     * every time a review is created, updated, or deleted.
     */
    private function updateRiderReviewRank(RiderProfile $riderProfile): void
    {
        $average = Review::where('rider_profile_id', $riderProfile->id)
                         ->avg('score');

        $riderProfile->update([
            'review_rank' => $average ? round($average, 1) : null,
        ]);
    }

    private function validationError($errors): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors'  => $errors,
        ], 422);
    }

}
