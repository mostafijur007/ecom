<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\VendorController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\Admin\ProductController;
use App\Http\Controllers\Api\V1\Admin\OrderController;
use App\Http\Controllers\Api\V1\Vendor\OrderController as VendorOrderController;
use App\Http\Controllers\Api\V1\Vendor\ProductController as VendorProductController;

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
| Architecture: Service-Repository Pattern
| - Controllers handle HTTP concerns only
| - Services contain business logic
| - Repositories handle data access
|
*/

Route::prefix('v1')->group(function () {
    
    // ========================================================================
    // PUBLIC ROUTES - No authentication required
    // ========================================================================
    
    // Registration - Limited to 2 per hour per IP
    Route::post('auth/register', [AuthController::class, 'register'])
        ->middleware('throttle:register');
    
    // Login - Limited to 3 per minute per email, 10 per hour
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:login');
    
    // Refresh token - Limited to 5 per minute
    Route::post('auth/refresh', [AuthController::class, 'refresh'])
        ->middleware('throttle:auth');

    // ========================================================================
    // PROTECTED ROUTES - Require authentication (auth:api middleware)
    // ========================================================================
    Route::middleware(['auth:api', 'throttle:authenticated'])->group(function () {
        
        // Authentication endpoints
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);

        // ====================================================================
        // ADMIN ROUTES - Full access to all resources
        // ====================================================================
        Route::prefix('admin')->middleware(['role:admin', 'throttle:admin'])->group(function () {
            // Dashboard & User Management
            Route::get('dashboard', [AdminController::class, 'dashboard']);
            Route::get('users', [AdminController::class, 'users']);
            Route::put('users/{id}', [AdminController::class, 'manageUser']);
            Route::delete('users/{id}', [AdminController::class, 'manageUser']);
            
            // Product Management - Full CRUD
            Route::prefix('products')->group(function () {
                Route::get('/', [ProductController::class, 'index'])
                    ->name('admin.products.index');
                Route::post('/', [ProductController::class, 'store'])
                    ->name('admin.products.store');
                Route::get('/search', [ProductController::class, 'search'])
                    ->name('admin.products.search');
                Route::get('/low-stock', [ProductController::class, 'lowStock'])
                    ->name('admin.products.lowStock');
                Route::post('/bulk-import', [ProductController::class, 'bulkImport'])
                    ->name('admin.products.bulkImport');
                Route::get('/{id}', [ProductController::class, 'show'])
                    ->name('admin.products.show');
                Route::put('/{id}', [ProductController::class, 'update'])
                    ->name('admin.products.update');
                Route::delete('/{id}', [ProductController::class, 'destroy'])
                    ->name('admin.products.destroy');
            });
            
            // Order Management - Full CRUD
            Route::prefix('orders')->group(function () {
                Route::get('/', [OrderController::class, 'index'])
                    ->name('admin.orders.index');
                Route::post('/', [OrderController::class, 'store'])
                    ->name('admin.orders.store');
                Route::get('/pending', [OrderController::class, 'pending'])
                    ->name('admin.orders.pending');
                Route::get('/statistics', [OrderController::class, 'statistics'])
                    ->name('admin.orders.statistics');
                Route::get('/{id}', [OrderController::class, 'show'])
                    ->name('admin.orders.show');
                Route::patch('/{id}/status', [OrderController::class, 'updateStatus'])
                    ->name('admin.orders.updateStatus');
                Route::patch('/{id}/payment', [OrderController::class, 'updatePayment'])
                    ->name('admin.orders.updatePayment');
                Route::post('/{id}/cancel', [OrderController::class, 'cancel'])
                    ->name('admin.orders.cancel');
            });
        });

        // ====================================================================
        // VENDOR ROUTES - Manage own products and orders
        // ====================================================================
        Route::prefix('vendor')->middleware(['role:vendor', 'throttle:vendor'])->group(function () {
            Route::get('dashboard', [VendorController::class, 'dashboard']);
            
            // Product Management - Vendor owns products
            Route::prefix('products')->group(function () {
                Route::get('/', [VendorProductController::class, 'index'])
                    ->name('vendor.products.index');
                Route::post('/', [VendorProductController::class, 'store'])
                    ->middleware('throttle:products')
                    ->name('vendor.products.store');
                Route::get('/search', [VendorProductController::class, 'search'])
                    ->name('vendor.products.search');
                Route::get('/low-stock', [VendorProductController::class, 'lowStock'])
                    ->name('vendor.products.lowStock');
                Route::post('/bulk-import', [VendorProductController::class, 'bulkImport'])
                    ->middleware('throttle:products')
                    ->name('vendor.products.bulkImport');
                Route::get('/{id}', [VendorProductController::class, 'show'])
                    ->name('vendor.products.show');
                Route::put('/{id}', [VendorProductController::class, 'update'])
                    ->middleware('throttle:products')
                    ->name('vendor.products.update');
                Route::delete('/{id}', [VendorProductController::class, 'destroy'])
                    ->name('vendor.products.destroy');
            });
            
            // Order Management - Vendor's orders only
            Route::prefix('orders')->group(function () {
                Route::get('/', [VendorOrderController::class, 'index'])
                    ->name('vendor.orders.index');
                Route::get('/recent', [VendorOrderController::class, 'recent'])
                    ->name('vendor.orders.recent');
                Route::get('/statistics', [VendorOrderController::class, 'statistics'])
                    ->name('vendor.orders.statistics');
                Route::get('/{id}', [VendorOrderController::class, 'show'])
                    ->name('vendor.orders.show');
                Route::patch('/{id}/status', [VendorOrderController::class, 'updateStatus'])
                    ->name('vendor.orders.updateStatus');
            });
        });

        // ====================================================================
        // CUSTOMER ROUTES - Place orders and view order history
        // ====================================================================
        Route::prefix('customer')->middleware(['role:customer', 'throttle:customer'])->group(function () {
            Route::get('dashboard', [CustomerController::class, 'dashboard']);
            
            // Order management - Limited to 5/min, 20/hour
            Route::post('orders', [CustomerController::class, 'placeOrder'])
                ->middleware('throttle:orders');
            Route::get('orders', [CustomerController::class, 'orderHistory']);
            Route::get('orders/{id}', [CustomerController::class, 'orderDetails']);
            Route::delete('orders/{id}', [CustomerController::class, 'cancelOrder']);
            
            // Profile management
            Route::get('profile', [CustomerController::class, 'profile']);
            Route::put('profile', [CustomerController::class, 'updateProfile']);
        });
    });
});
