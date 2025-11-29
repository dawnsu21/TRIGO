<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Place;
use Illuminate\Http\Request;

class PlaceController extends Controller
{
    /**
     * Get all active places
     */
    public function index(Request $request)
    {
        $query = Place::active();

        // Filter by category
        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        // Search
        if ($request->has('q') && ! empty($request->q)) {
            $query->search($request->q);
        }

        $places = $query->orderBy('name')->get();

        return response()->json([
            'data' => $places,
        ]);
    }

    /**
     * Search places
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2'],
        ]);

        $places = Place::active()
            ->search($request->q)
            ->orderBy('name')
            ->limit(20)
            ->get();

        return response()->json([
            'data' => $places,
        ]);
    }

    /**
     * Get single place
     */
    public function show(Place $place)
    {
        return response()->json([
            'data' => $place,
        ]);
    }

    /**
     * Create new place (Admin only)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'address'   => ['nullable', 'string'],
            'latitude'  => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'category' => ['required', 'string', 'in:landmark,barangay,establishment,school,government'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $place = Place::create($validated);

        return response()->json([
            'message' => 'Place created successfully',
            'data'    => $place,
        ], 201);
    }

    /**
     * Update place (Admin only)
     */
    public function update(Request $request, Place $place)
    {
        $validated = $request->validate([
            'name'      => ['sometimes', 'required', 'string', 'max:255'],
            'address'   => ['nullable', 'string'],
            'latitude'  => ['sometimes', 'required', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'required', 'numeric', 'between:-180,180'],
            'category'  => ['sometimes', 'required', 'string', 'in:landmark,barangay,establishment,school,government'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $place->update($validated);

        return response()->json([
            'message' => 'Place updated successfully',
            'data'    => $place->fresh(),
        ]);
    }

    /**
     * Delete place (Admin only)
     */
    public function destroy(Place $place)
    {
        $place->delete();

        return response()->json([
            'message' => 'Place deleted successfully',
        ]);
    }
}
