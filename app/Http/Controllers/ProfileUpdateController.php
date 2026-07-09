<?php

namespace App\Http\Controllers;

use App\Models\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Http\Request;

class ProfileUpdateController extends Controller
{
    /**
     * Submit a profile update request (for riders/users)
     */
    public function submitUpdate(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $validated = $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'phone' => 'required|string',
        ]);

        // Get old data from current user
        $oldData = [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'phone' => $user->phone,
        ];

        // Create profile update request
        $updateRequest = ProfileUpdateRequest::create([
            'user_id' => $user->id,
            'old_data' => $oldData,
            'new_data' => $validated,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profile update request submitted for admin review',
            'data' => $updateRequest,
        ], 201);
    }

    /**
     * Get pending profile update requests (admin only)
     */
    public function getPending()
    {
        $requests = ProfileUpdateRequest::pending()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }

    /**
     * Approve a profile update request (admin only)
     */
    public function approve(Request $request, $id)
    {
        $updateRequest = ProfileUpdateRequest::find($id);
        if (!$updateRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Profile update request not found'
            ], 404);
        }

        $user = $updateRequest->user;
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Update user profile with new data
        $user->update($updateRequest->new_data);

        // If user is a rider, also update RiderProfile to keep names in sync
        if ($user->riderProfile && isset($updateRequest->new_data['first_name'])) {
            $user->riderProfile->update([
                'first_name' => $updateRequest->new_data['first_name'],
                'last_name' => $updateRequest->new_data['last_name'] ?? $user->riderProfile->last_name,
            ]);
        }

        // Update request status
        $updateRequest->update([
            'status' => 'approved',
            'admin_note' => $request->input('admin_note'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profile update approved',
            'data' => $updateRequest,
        ]);
    }

    /**
     * Reject a profile update request (admin only)
     */
    public function reject(Request $request, $id)
    {
        $updateRequest = ProfileUpdateRequest::find($id);
        if (!$updateRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Profile update request not found'
            ], 404);
        }

        $updateRequest->update([
            'status' => 'rejected',
            'admin_note' => $request->input('admin_note'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profile update rejected',
            'data' => $updateRequest,
        ]);
    }
}
