<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\VendorController;
use App\Http\Controllers\Api\V1\CustomerController;

/*
|--------------------------------------------------------------------------
| API Routes - JWT Authentication with Role-Based Access Control
|--------------------------------------------------------------------------
|
| This file defines all API routes with JWT authentication and role-based
| access control. Three roles are implemented:
| - Admin: Full system access
| - Vendor: Manage own products and orders
| - Customer: Place orders and view order history
|
*/

Route::prefix('v1')->group(function () {
    
    // ========================================================================
    // PUBLIC ROUTES - No authentication required
    // ========================================================================
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/refresh', [AuthController::class, 'refresh']);

    // ========================================================================
    // PROTECTED ROUTES - Require authentication (auth:api middleware)
    // ========================================================================
    Route::middleware('auth:api')->group(function () {
        
        // Authentication endpoints
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);

        // ====================================================================
        // ADMIN ROUTES - Full access to all resources
        // ====================================================================
        Route::prefix('admin')->middleware('role:admin')->group(function () {
            Route::get('dashboard', [AdminController::class, 'dashboard']);
            Route::get('users', [AdminController::class, 'users']);
            Route::put('users/{id}', [AdminController::class, 'manageUser']);
            Route::delete('users/{id}', [AdminController::class, 'manageUser']);
        });

        // ====================================================================
        // VENDOR ROUTES - Manage own products and orders
        // ====================================================================
        Route::prefix('vendor')->middleware('role:vendor')->group(function () {
            Route::get('dashboard', [VendorController::class, 'dashboard']);
            
            // Product management
            Route::get('products', [VendorController::class, 'products']);
            Route::post('products', [VendorController::class, 'createProduct']);
            Route::put('products/{id}', [VendorController::class, 'updateProduct']);
            
            // Order management
            Route::get('orders', [VendorController::class, 'orders']);
            Route::put('orders/{id}/status', [VendorController::class, 'updateOrderStatus']);
        });

        // ====================================================================
        // CUSTOMER ROUTES - Place orders and view order history
        // ====================================================================
        Route::prefix('customer')->middleware('role:customer')->group(function () {
            Route::get('dashboard', [CustomerController::class, 'dashboard']);
            
            // Order management
            Route::post('orders', [CustomerController::class, 'placeOrder']);
            Route::get('orders', [CustomerController::class, 'orderHistory']);
            Route::get('orders/{id}', [CustomerController::class, 'orderDetails']);
            Route::delete('orders/{id}', [CustomerController::class, 'cancelOrder']);
            
            // Profile management
            Route::get('profile', [CustomerController::class, 'profile']);
            Route::put('profile', [CustomerController::class, 'updateProfile']);
        });
    });
});
