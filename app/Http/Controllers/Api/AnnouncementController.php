<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    /**
     * Get active announcements for user based on their role
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $role = $user->getRoleNames()->first() ?? 'passenger';

        $audience = match($role) {
            'admin' => Announcement::AUDIENCE_ADMINS,
            'driver' => Announcement::AUDIENCE_DRIVERS,
            default => Announcement::AUDIENCE_PASSENGERS,
        };

        $announcements = Announcement::active()
            ->where(function($q) use ($audience) {
                $q->where('target_audience', Announcement::AUDIENCE_ALL)
                  ->orWhere('target_audience', $audience);
            })
            ->with(['admin:id,name'])
            ->latest()
            ->get();

        return response()->json(['data' => $announcements]);
    }

    /**
     * Admin: Create announcement
     */
    public function store(Request $request)
    {
        $admin = $request->user();

        if (!$admin->hasRole('admin')) {
            return response()->json(['message' => 'Only admins can create announcements'], 403);
        }

        $validated = $request->validate([
            'type' => ['required', 'in:maintenance,system_update,general,urgent'],
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'target_audience' => ['required', 'in:all,drivers,passengers,admins'],
        ]);

        $announcement = Announcement::create([
            'admin_id' => $admin->id,
            'type' => $validated['type'],
            'title' => $validated['title'],
            'message' => $validated['message'],
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'target_audience' => $validated['target_audience'],
            'is_active' => true,
        ]);

        // Notify all targeted users
        NotificationService::notifySystemAnnouncement(
            $validated['title'],
            $validated['message'],
            $validated['target_audience']
        );

        return response()->json([
            'message' => 'Announcement created and users notified',
            'data' => $announcement->load(['admin:id,name']),
        ], 201);
    }

    /**
     * Admin: Update announcement
     */
    public function update(Request $request, Announcement $announcement)
    {
        $admin = $request->user();

        if (!$admin->hasRole('admin')) {
            return response()->json(['message' => 'Only admins can update announcements'], 403);
        }

        $validated = $request->validate([
            'type' => ['sometimes', 'in:maintenance,system_update,general,urgent'],
            'title' => ['sometimes', 'string', 'max:255'],
            'message' => ['sometimes', 'string', 'max:2000'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'target_audience' => ['sometimes', 'in:all,drivers,passengers,admins'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $announcement->update($validated);

        return response()->json([
            'message' => 'Announcement updated',
            'data' => $announcement->load(['admin:id,name']),
        ]);
    }

    /**
     * Admin: Delete announcement
     */
    public function destroy(Request $request, Announcement $announcement)
    {
        $admin = $request->user();

        if (!$admin->hasRole('admin')) {
            return response()->json(['message' => 'Only admins can delete announcements'], 403);
        }

        $announcement->delete();

        return response()->json(['message' => 'Announcement deleted']);
    }

    /**
     * Admin: List all announcements
     */
    public function adminIndex(Request $request)
    {
        $admin = $request->user();

        if (!$admin->hasRole('admin')) {
            return response()->json(['message' => 'Only admins can view all announcements'], 403);
        }

        $announcements = Announcement::with(['admin:id,name'])
            ->latest()
            ->paginate($request->input('per_page', 20));

        return response()->json($announcements);
    }
}

