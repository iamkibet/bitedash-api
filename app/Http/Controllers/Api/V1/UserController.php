<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Get list of users, optionally filtered by role.
     * Accessible by restaurant managers (to assign riders) and admins.
     */
    public function index(Request $request): JsonResponse
    {
        // Only restaurant managers and admins can view users
        if (!$request->user()->isRestaurant() && !$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Unauthorized. Only restaurant managers and admins can view users.',
            ], 403);
        }

        $request->validate([
            'role' => ['sometimes', 'string', Rule::in(['customer', 'restaurant', 'rider', 'admin'])],
        ]);

        $query = User::query();

        // Filter by role if provided
        if ($request->has('role')) {
            $query->where('role', $request->input('role'));
        }

        $users = $query->latest()->paginate(15);

        return response()->json([
            'data' => UserResource::collection($users->items()),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * Get list of riders (convenience endpoint).
     * Accessible by restaurant managers (to assign riders) and admins.
     */
    public function riders(Request $request): JsonResponse
    {
        // Only restaurant managers and admins can view riders
        if (!$request->user()->isRestaurant() && !$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Unauthorized. Only restaurant managers and admins can view riders.',
            ], 403);
        }

        $riders = User::where('role', 'rider')
            ->latest()
            ->paginate(15);

        return response()->json([
            'data' => UserResource::collection($riders->items()),
            'meta' => [
                'current_page' => $riders->currentPage(),
                'last_page' => $riders->lastPage(),
                'per_page' => $riders->perPage(),
                'total' => $riders->total(),
            ],
        ]);
    }
}
