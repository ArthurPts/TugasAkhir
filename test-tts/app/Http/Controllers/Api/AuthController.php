<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:45', 'unique:users,username'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'username' => $validated['username'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role'     => 'user',
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ], 201);
    }

    /**
     * Login user and issue token.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'login'    => ['required', 'string'], // email or username
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['login'])
            ->orWhere('username', $validated['login'])
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Kredensial yang diberikan tidak cocok dengan data kami.'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ]);
    }

    /**
     * Logout user and revoke token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Berhasil logout.',
        ]);
    }

    /**
     * Get authenticated user profile.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    /**
     * Get quota information for authenticated user.
     */
    public function quota(Request $request): JsonResponse
    {
        $user = $request->user();
        $isAdmin = $user->role === 'admin';
        $activeSongs = $user->songs()->count();
        $maxSongs = $isAdmin ? null : 10;
        $remaining = $isAdmin ? null : max(0, 10 - $activeSongs);

        return response()->json([
            'role'         => $user->role,
            'active_songs' => $activeSongs,
            'max_songs'    => $maxSongs,
            'remaining'    => $remaining,
        ]);
    }
}
