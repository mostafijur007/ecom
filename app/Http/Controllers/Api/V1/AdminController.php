<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    use ApiResponse;

    /**
     * Get dashboard statistics
     */
    public function dashboard(): JsonResponse
    {
        return $this->successResponse([
            'total_users' => 100,
            'total_vendors' => 25,
            'total_customers' => 75,
            'total_orders' => 500,
            'total_revenue' => 50000,
        ], 'Admin dashboard data');
    }

    /**
     * Get all users
     */
    public function users(): JsonResponse
    {
        return $this->successResponse([
            'users' => \App\Models\User::paginate(15)
        ], 'All users retrieved');
    }

    /**
     * Manage user (update, delete, etc.)
     */
    public function manageUser(Request $request, $id): JsonResponse
    {
        return $this->successResponse([
            'note' => 'Implement user update/delete logic here'
        ], 'User management endpoint');
    }
}
