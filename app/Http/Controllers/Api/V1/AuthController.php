<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RefreshToken;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    use ApiResponse;
    /**
     * Register a new user
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,vendor,customer',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        $token = JWTAuth::fromUser($user);
        $refreshToken = $this->createRefreshToken($user, $request);

        return $this->createdResponse([
            'user' => $user,
            'access_token' => $token,
            'refresh_token' => $refreshToken->token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60
        ], 'User registered successfully');
    }

    /**
     * Login user
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $credentials = $request->only('email', 'password');

        if (!$token = auth('api')->attempt($credentials)) {
            return $this->unauthorizedResponse('Invalid credentials');
        }

        $user = auth('api')->user();
        $refreshToken = $this->createRefreshToken($user, $request);

        return $this->successResponse([
            'user' => $user,
            'access_token' => $token,
            'refresh_token' => $refreshToken->token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ], 'Login successful');
    }

    /**
     * Refresh access token using refresh token
     */
    public function refresh(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'refresh_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        // Use the new findByToken method that searches by hash
        $refreshToken = RefreshToken::findByToken($request->refresh_token);

        if (!$refreshToken) {
            return $this->unauthorizedResponse('Invalid refresh token');
        }

        if ($refreshToken->isExpired()) {
            $refreshToken->delete();
            return $this->unauthorizedResponse('Refresh token has expired');
        }

        $user = $refreshToken->user;
        $newAccessToken = JWTAuth::fromUser($user);
        
        // Optionally rotate refresh token
        $refreshToken->delete();
        $newRefreshToken = $this->createRefreshToken($user, $request);

        return $this->successResponse([
            'access_token' => $newAccessToken,
            'refresh_token' => $newRefreshToken->token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60
        ], 'Token refreshed successfully');
    }

    /**
     * Get authenticated user
     */
    public function me(): JsonResponse
    {
        $user = auth('api')->user();

        return $this->successResponse(['user' => $user], 'User retrieved successfully');
    }

    /**
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {
        $user = auth('api')->user();

        // Delete all refresh tokens for this user
        $user->refreshTokens()->delete();

        // Invalidate the access token
        auth('api')->logout();

        return $this->successResponse(null, 'Logged out successfully');
    }

    /**
     * Create a refresh token for the user
     */
    private function createRefreshToken(User $user, Request $request): RefreshToken
    {
        // Delete old refresh tokens (optional: keep only last N tokens)
        $user->refreshTokens()->where('expires_at', '<', now())->delete();

        return RefreshToken::create([
            'user_id' => $user->id,
            'token' => Str::random(64),
            'expires_at' => now()->addDays(30), // Refresh token valid for 30 days
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
