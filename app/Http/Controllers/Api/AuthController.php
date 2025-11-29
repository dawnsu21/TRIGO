<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\DriverProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Unified registration endpoint - accepts role parameter
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'email'            => ['required', 'email', 'unique:users,email'],
            'password'         => ['required', Password::min(6)],
            'role'             => ['required', 'string', 'in:passenger,driver'],
            // Driver-specific fields - accept multiple field name variations
            'vehicle_type'     => ['required_if:role,driver', 'nullable', 'string', 'max:255'],
            'vehicle_make'     => ['required_if:role,driver', 'nullable', 'string', 'max:255'],
            'vehicle_model'    => ['nullable', 'string', 'max:255'],
            'plate_number'     => ['required_if:role,driver', 'nullable', 'string', 'max:255'],
            'license_plate'    => ['required_if:role,driver', 'nullable', 'string', 'max:255'],
            'license_number'   => ['required_if:role,driver', 'nullable', 'string', 'max:255'],
            'franchise_number' => ['nullable', 'string', 'max:255'],
            'phone'            => ['nullable', 'string', 'max:255'], // Optional phone field
        ]);

        $role = $validated['role'];

        if ($role === 'driver') {
            return $this->registerDriver($request);
        } else {
            return $this->registerPassenger($request);
        }
    }

    public function registerPassenger(Request $request)
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', Password::min(6)],
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole('passenger');

        $token = $user->createToken('apiToken')->plainTextToken;

        return response()->json([
            'message' => 'Passenger registered successfully',
            'data'    => [
                'user'  => $user,
                'token' => $token,
                'role'  => 'passenger',
            ],
        ], 201);
    }

    public function registerDriver(Request $request)
    {
        $validated = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'email'            => ['required', 'email', 'unique:users,email'],
            'password'         => ['required', Password::min(6)],
            // Accept multiple field name variations from frontend
            'vehicle_type'     => ['nullable', 'string', 'max:255'],
            'vehicle_make'     => ['required_without:vehicle_type', 'nullable', 'string', 'max:255'],
            'vehicle_model'    => ['nullable', 'string', 'max:255'],
            'plate_number'     => ['nullable', 'string', 'max:255'],
            'license_plate'    => ['required_without:plate_number', 'nullable', 'string', 'max:255'],
            'license_number'   => ['required', 'string', 'max:255'],
            'franchise_number' => ['nullable', 'string', 'max:255'],
            'phone'            => ['nullable', 'string', 'max:255'],
        ]);

        // Combine vehicle_make and vehicle_model into vehicle_type if vehicle_type not provided
        $vehicleType = $validated['vehicle_type'] ?? null;
        if (! $vehicleType && isset($validated['vehicle_make'])) {
            $vehicleType = $validated['vehicle_make'];
            if (isset($validated['vehicle_model']) && ! empty($validated['vehicle_model'])) {
                $vehicleType .= ' ' . $validated['vehicle_model'];
            }
        }

        // Use license_plate if plate_number not provided
        $plateNumber = $validated['plate_number'] ?? $validated['license_plate'] ?? null;

        // Validate that we have required fields
        if (! $vehicleType) {
            throw ValidationException::withMessages([
                'vehicle_type' => ['Vehicle type is required. Provide either vehicle_type or vehicle_make.'],
            ]);
        }

        if (! $plateNumber) {
            throw ValidationException::withMessages([
                'plate_number' => ['License plate is required. Provide either plate_number or license_plate.'],
            ]);
        }

        $user = DB::transaction(function () use ($validated, $vehicleType, $plateNumber) {
            $newUser = User::create([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $newUser->assignRole('driver');

            DriverProfile::create([
                'user_id'          => $newUser->id,
                'vehicle_type'     => $vehicleType,
                'plate_number'     => $plateNumber,
                'license_number'   => $validated['license_number'],
                'franchise_number' => $validated['franchise_number'] ?? null,
                'status'           => DriverProfile::STATUS_PENDING,
            ]);

            return $newUser;
        });

        $token = $user->createToken('apiToken')->plainTextToken;

        return response()->json([
            'message' => 'Driver registered. Awaiting admin approval.',
            'data'    => [
                'user'  => $user->load('driverProfile'),
                'token' => $token,
                'role'  => 'driver',
            ],
        ], 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email'       => ['nullable', 'email'],
            'identifier'  => ['nullable', 'string'],
            'password'    => ['required', 'string'],
            'device_name' => ['nullable', 'string'],
        ]);

        if (! $validated['email'] && ! $validated['identifier']) {
            throw ValidationException::withMessages([
                'email' => ['Provide either an email or username.'],
            ]);
        }

        $loginValue = strtolower(trim($validated['email'] ?? $validated['identifier']));
        $user = User::whereRaw('LOWER(email) = ?', [$loginValue])
            ->orWhereRaw('LOWER(name) = ?', [$loginValue])
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $tokenName = $validated['device_name'] ?? 'apiToken';
        $token = $user->createToken($tokenName)->plainTextToken;

        // Get user roles for dashboard routing
        $roles = $user->getRoleNames();
        $primaryRole = $roles->first() ?? 'passenger'; // Default to passenger if no role

        return response()->json([
            'message' => 'Login successful',
            'token'   => $token,
            'user'    => $user->load('driverProfile'),
            'role'    => $primaryRole,
            'roles'   => $roles,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('driverProfile');
        $roles = $user->getRoleNames();
        $primaryRole = $roles->first() ?? 'passenger';

        return response()->json([
            'data' => $user,
            'role' => $primaryRole,
            'roles' => $roles,
        ]);
    }
}
