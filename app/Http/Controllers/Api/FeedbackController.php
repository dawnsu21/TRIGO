<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\Ride;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    /**
     * Submit feedback/rating for a completed ride
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $role = $user->getRoleNames()->first();

        if (!in_array($role, ['driver', 'passenger'])) {
            return response()->json(['message' => 'Only drivers and passengers can submit feedback'], 403);
        }

        $validated = $request->validate([
            'ride_id' => ['required', 'exists:rides,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $ride = Ride::findOrFail($validated['ride_id']);

        // Verify ride belongs to user and is completed
        if ($role === 'passenger') {
            if ($ride->passenger_id !== $user->id) {
                return response()->json(['message' => 'You can only rate rides you were a passenger in'], 403);
            }
        } elseif ($role === 'driver') {
            if ($ride->driver_id !== $user->id) {
                return response()->json(['message' => 'You can only rate rides you were a driver in'], 403);
            }
        }

        if ($ride->status !== Ride::STATUS_COMPLETED) {
            return response()->json(['message' => 'You can only rate completed rides'], 422);
        }

        // Check if feedback already exists
        $existingFeedback = Feedback::where('ride_id', $ride->id)
            ->where('from_user_id', $user->id)
            ->first();

        if ($existingFeedback) {
            return response()->json(['message' => 'You have already submitted feedback for this ride'], 409);
        }

        // Determine who receives the feedback
        $toUserId = $role === 'passenger' ? $ride->driver_id : $ride->passenger_id;

        if (!$toUserId) {
            return response()->json(['message' => 'Cannot submit feedback: other party not found'], 422);
        }

        $feedback = Feedback::create([
            'ride_id' => $ride->id,
            'from_user_id' => $user->id,
            'to_user_id' => $toUserId,
            'from_role' => $role,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
        ]);

        return response()->json([
            'message' => 'Feedback submitted successfully',
            'data' => $feedback->load(['fromUser:id,name', 'toUser:id,name', 'ride:id,status']),
        ], 201);
    }

    /**
     * Get feedbacks received by user
     */
    public function myFeedbacks(Request $request)
    {
        $user = $request->user();

        $feedbacks = Feedback::where('to_user_id', $user->id)
            ->with([
                'fromUser:id,name',
                'ride:id,status,fare,completed_at',
            ])
            ->latest()
            ->paginate($request->input('per_page', 20));

        // Calculate average rating
        $averageRating = Feedback::where('to_user_id', $user->id)
            ->avg('rating');

        return response()->json([
            'average_rating' => round($averageRating ?? 0, 2),
            'total_feedbacks' => Feedback::where('to_user_id', $user->id)->count(),
            'data' => $feedbacks,
        ]);
    }

    /**
     * Get feedbacks for a specific ride
     */
    public function rideFeedback(Request $request, Ride $ride)
    {
        $user = $request->user();

        // Verify user is part of the ride
        if ($ride->passenger_id !== $user->id && $ride->driver_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized access to this ride'], 403);
        }

        $feedbacks = Feedback::where('ride_id', $ride->id)
            ->with(['fromUser:id,name'])
            ->get();

        return response()->json(['data' => $feedbacks]);
    }
}

