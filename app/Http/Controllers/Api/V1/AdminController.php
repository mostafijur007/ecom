<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function dashboard(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Admin dashboard data',
            'data' => [
                'total_users' => 100,
                'total_vendors' => 25,
                'total_customers' => 75,
                'total_orders' => 500,
                'total_revenue' => 50000,
            ]
        ]);
    }

    /**
     * Get all users
     */
    public function users(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'All users retrieved',
            'data' => [
                'users' => \App\Models\User::paginate(15)
            ]
        ]);
    }

    /**
     * Manage user (update, delete, etc.)
     */
    public function manageUser(Request $request, $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'User management endpoint',
            'note' => 'Implement user update/delete logic here'
        ]);
    }
}
