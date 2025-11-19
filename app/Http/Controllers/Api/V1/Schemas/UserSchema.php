<?php

namespace App\Http\Controllers\Api\V1\Schemas;

/**
 * @OA\Schema(
 *     schema="User",
 *     title="User",
 *     description="User model with authentication and profile information",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *     @OA\Property(
 *         property="role",
 *         type="string",
 *         enum={"admin", "vendor", "customer"},
 *         example="vendor"
 *     ),
 *     @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true, example="2024-01-01T00:00:00Z"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00Z")
 * )
 */
class UserSchema
{
    // This class exists solely for OpenAPI documentation
}
