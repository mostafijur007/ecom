<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="E-Commerce API",
 *     version="1.0.0",
 *     description="API documentation for E-Commerce platform with role-based access control",
 *     @OA\Contact(
 *         email="support@ecom.com"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="http://localhost:8000/api/v1",
 *     description="Local Development Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter your JWT token in the format: Bearer {token}"
 * )
 * 
 * @OA\Tag(
 *     name="Authentication",
 *     description="User authentication endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Admin",
 *     description="Admin-only endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Vendor",
 *     description="Vendor-specific endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Customer",
 *     description="Customer-specific endpoints"
 * )
 */
abstract class Controller
{
    //
}
