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
     * @OA\Post(
     *     path="/auth/register",
     *     tags={"Authentication"},
     *     summary="Register a new user",
     *     description="Register a new user account with role selection",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "password_confirmation", "role"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="password123"),
     *             @OA\Property(property="role", type="string", enum={"admin", "vendor", "customer"}, example="customer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User registered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User registered successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object"),
     *                 @OA\Property(property="access_token", type="string"),
     *                 @OA\Property(property="refresh_token", type="string"),
     *                 @OA\Property(property="token_type", type="string", example="bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=3600)
     *             ),
     *             @OA\Property(property="errors", type="null")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="data", type="null"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
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
     * @OA\Post(
     *     path="/auth/login",
     *     tags={"Authentication"},
     *     summary="Login user",
     *     description="Authenticate user and get access token",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="customer@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Login successful"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object"),
     *                 @OA\Property(property="access_token", type="string"),
     *                 @OA\Property(property="refresh_token", type="string"),
     *                 @OA\Property(property="token_type", type="string", example="bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=3600)
     *             ),
     *             @OA\Property(property="errors", type="null")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials"
     *     )
     * )
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
     * @OA\Post(
     *     path="/auth/refresh",
     *     tags={"Authentication"},
     *     summary="Refresh access token",
     *     description="Get a new access token using refresh token",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"refresh_token"},
     *             @OA\Property(property="refresh_token", type="string", example="your_refresh_token_here")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token refreshed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Token refreshed successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="access_token", type="string"),
     *                 @OA\Property(property="refresh_token", type="string"),
     *                 @OA\Property(property="token_type", type="string", example="bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=3600)
     *             ),
     *             @OA\Property(property="errors", type="null")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid or expired refresh token"
     *     )
     * )
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
     * @OA\Get(
     *     path="/auth/me",
     *     tags={"Authentication"},
     *     summary="Get authenticated user",
     *     description="Get current authenticated user information",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="john@example.com"),
     *                     @OA\Property(property="role", type="string", example="customer")
     *                 )
     *             ),
     *             @OA\Property(property="errors", type="null")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function me(): JsonResponse
    {
        $user = auth('api')->user();

        return $this->successResponse(['user' => $user], 'User retrieved successfully');
    }

    /**
     * @OA\Post(
     *     path="/auth/logout",
     *     tags={"Authentication"},
     *     summary="Logout user",
     *     description="Logout user and invalidate tokens",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logged out successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Logged out successfully"),
     *             @OA\Property(property="data", type="null"),
     *             @OA\Property(property="errors", type="null")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
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
