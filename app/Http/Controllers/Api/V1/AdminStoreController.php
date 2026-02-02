<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminUpdateStoreRequest;
use App\Http\Resources\RestaurantResource;
use App\Models\Restaurant;
use App\Services\RestaurantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminStoreController extends Controller
{
    public function __construct(
        private readonly RestaurantService $restaurantService
    ) {
    }

    /**
     * List all stores (admin only). Paginated.
     * GET /api/v1/admin/stores?page=1&per_page=15
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $perPage = min(max($perPage, 1), 100);

        $restaurants = Restaurant::with(['owner', 'menuItems'])
            ->paginate($perPage);

        return response()->json([
            'data' => RestaurantResource::collection($restaurants->items()),
            'meta' => [
                'current_page' => $restaurants->currentPage(),
                'last_page' => $restaurants->lastPage(),
                'per_page' => $restaurants->perPage(),
                'total' => $restaurants->total(),
            ],
        ]);
    }

    /**
     * Update a store (e.g. is_open). Admin only.
     * PUT/PATCH /api/v1/admin/stores/{id}
     */
    public function update(AdminUpdateStoreRequest $request, Restaurant $restaurant): JsonResponse
    {
        $restaurant = $this->restaurantService->update(
            $restaurant,
            $request->validated(),
            null
        );

        return response()->json([
            'message' => 'Store updated successfully.',
            'data' => new RestaurantResource($restaurant->load(['owner', 'menuItems'])),
        ]);
    }
}
