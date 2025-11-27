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

        return response()->json([
            'message' => 'Passenger registered successfully',
            'data'    => [
                'user'  => $user,
                'token' => $user->createToken('apiToken')->plainTextToken,
            ],
        ], 201);
    }

    public function registerDriver(Request $request)
    {
        $validated = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'email'            => ['required', 'email', 'unique:users,email'],
            'password'         => ['required', Password::min(6)],
            'vehicle_type'     => ['required', 'string', 'max:255'],
            'plate_number'     => ['required', 'string', 'max:255'],
            'license_number'   => ['required', 'string', 'max:255'],
            'franchise_number' => ['nullable', 'string', 'max:255'],
        ]);

        $user = DB::transaction(function () use ($validated) {
            $newUser = User::create([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $newUser->assignRole('driver');

            DriverProfile::create([
                'user_id'          => $newUser->id,
                'vehicle_type'     => $validated['vehicle_type'],
                'plate_number'     => $validated['plate_number'],
                'license_number'   => $validated['license_number'],
                'franchise_number' => $validated['franchise_number'] ?? null,
                'status'           => DriverProfile::STATUS_PENDING,
            ]);

            return $newUser;
        });

        return response()->json([
            'message' => 'Driver registered. Awaiting admin approval.',
            'data'    => [
                'user'  => $user,
                'token' => $user->createToken('apiToken')->plainTextToken,
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

        return response()->json([
            'message' => 'Login successful',
            'token'   => $token,
            'user'    => $user,
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

        return response()->json([
            'data' => $user,
        ]);
    }
}
