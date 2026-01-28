<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRestaurantRequest;
use App\Http\Requests\UpdateRestaurantRequest;
use App\Http\Resources\RestaurantResource;
use App\Models\Restaurant;
use App\Services\RestaurantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RestaurantController extends Controller
{
    public function __construct(
        private readonly RestaurantService $restaurantService
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        // Public route - no authorization required
        $isOpen = $request->boolean('is_open');
        $restaurants = $this->restaurantService->getAll($isOpen !== false ? $isOpen : null);

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
     * Store a newly created resource in storage.
     */
    public function store(StoreRestaurantRequest $request): JsonResponse
    {
        $restaurant = $this->restaurantService->create(
            $request->user(),
            $request->validated(),
            $request->file('image')
        );

        return response()->json([
            'message' => 'Restaurant created successfully.',
            'data' => new RestaurantResource($restaurant->load(['owner', 'menuItems'])),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Restaurant $restaurant): JsonResponse
    {
        // Public route - no authorization required
        $restaurant = $this->restaurantService->getById($restaurant->id);

        return response()->json([
            'data' => new RestaurantResource($restaurant),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRestaurantRequest $request, Restaurant $restaurant): JsonResponse
    {
        $restaurant = $this->restaurantService->update(
            $restaurant,
            $request->validated(),
            $request->file('image')
        );

        return response()->json([
            'message' => 'Restaurant updated successfully.',
            'data' => new RestaurantResource($restaurant->load(['owner', 'menuItems'])),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Restaurant $restaurant): JsonResponse
    {
        $this->authorize('delete', $restaurant);

        $this->restaurantService->delete($restaurant);

        return response()->json([
            'message' => 'Restaurant deleted successfully.',
        ]);
    }

    /**
     * Toggle restaurant open/closed status.
     */
    public function toggleStatus(Restaurant $restaurant): JsonResponse
    {
        $this->authorize('toggleStatus', $restaurant);

        $restaurant = $this->restaurantService->toggleStatus($restaurant);

        return response()->json([
            'message' => 'Restaurant status toggled successfully.',
            'data' => new RestaurantResource($restaurant->load(['owner', 'menuItems'])),
        ]);
    }

    /**
     * Get the authenticated restaurant owner's restaurant.
     */
    public function myStore(Request $request): JsonResponse
    {
        if (!$request->user()->isRestaurant()) {
            return response()->json([
                'message' => 'Only restaurant owners can access this endpoint.',
            ], 403);
        }

        $restaurant = $this->restaurantService->getByOwner($request->user());

        if (!$restaurant) {
            return response()->json([
                'message' => 'No restaurant found. Please create a restaurant first.',
            ], 404);
        }

        // Get order statistics including revenue
        $statistics = $this->restaurantService->getOrderStatistics($restaurant);

        $restaurantData = new RestaurantResource($restaurant->load(['owner', 'menuItems']));

        return response()->json([
            'data' => $restaurantData,
            'statistics' => $statistics,
        ]);
    }
}
