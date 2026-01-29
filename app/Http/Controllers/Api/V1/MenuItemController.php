<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMenuItemRequest;
use App\Http\Requests\UpdateMenuItemRequest;
use App\Http\Resources\MenuItemResource;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Services\MenuItemService;
use App\Services\RestaurantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MenuItemController extends Controller
{
    public function __construct(
        private readonly MenuItemService $menuItemService,
        private readonly RestaurantService $restaurantService
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Restaurant $restaurant): JsonResponse
    {
        // Public route - no authorization required
        $isAvailable = $request->boolean('is_available');
        $userId = $request->user()?->id;
        $menuItems = $this->menuItemService->getByRestaurant(
            $restaurant->id,
            $isAvailable !== false ? $isAvailable : null,
            $userId
        );

        return response()->json([
            'data' => MenuItemResource::collection($menuItems->items()),
            'meta' => [
                'current_page' => $menuItems->currentPage(),
                'last_page' => $menuItems->lastPage(),
                'per_page' => $menuItems->perPage(),
                'total' => $menuItems->total(),
            ],
        ]);
    }

    /**
     * Get menu items for the authenticated restaurant owner's restaurant.
     */
    public function myMenuItems(Request $request): JsonResponse
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

        $isAvailable = $request->boolean('is_available');
        $userId = $request->user()?->id;
        $menuItems = $this->menuItemService->getByRestaurant(
            $restaurant->id,
            $isAvailable !== false ? $isAvailable : null,
            $userId
        );

        return response()->json([
            'data' => MenuItemResource::collection($menuItems->items()),
            'meta' => [
                'current_page' => $menuItems->currentPage(),
                'last_page' => $menuItems->lastPage(),
                'per_page' => $menuItems->perPage(),
                'total' => $menuItems->total(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMenuItemRequest $request): JsonResponse
    {
        try {
            $restaurantId = $request->validated('restaurant_id');
            $restaurant = Restaurant::findOrFail($restaurantId);

            // Verify restaurant ownership
            if ($request->user()->id !== $restaurant->owner_id && !$request->user()->isAdmin()) {
                return response()->json([
                    'message' => 'Unauthorized to create menu items for this restaurant.',
                ], 403);
            }

            $menuItem = $this->menuItemService->create(
                $restaurant,
                $request->validated(),
                $request->file('image')
            );

            return response()->json([
                'message' => 'Menu item created successfully.',
                'data' => new MenuItemResource($menuItem->load('restaurant')),
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Restaurant not found.',
                'errors' => ['restaurant_id' => ['The specified restaurant does not exist.']],
            ], 404);
        } catch (\Exception $e) {
            Log::error('Menu item creation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Menu item creation failed. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred during menu item creation.',
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, MenuItem $menuItem): JsonResponse
    {
        // Public route - no authorization required
        $userId = $request->user()?->id;
        $menuItem = $this->menuItemService->getById($menuItem->id, $userId);

        return response()->json([
            'data' => new MenuItemResource($menuItem),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMenuItemRequest $request, MenuItem $menuItem): JsonResponse
    {
        try {
            $menuItem = $this->menuItemService->update(
                $menuItem,
                $request->validated(),
                $request->file('image')
            );

            return response()->json([
                'message' => 'Menu item updated successfully.',
                'data' => new MenuItemResource($menuItem->load('restaurant')),
            ]);
        } catch (\Exception $e) {
            Log::error('Menu item update error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Menu item update failed. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred during menu item update.',
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MenuItem $menuItem): JsonResponse
    {
        $this->authorize('delete', $menuItem);

        $this->menuItemService->delete($menuItem);

        return response()->json([
            'message' => 'Menu item deleted successfully.',
        ]);
    }

    /**
     * Toggle menu item availability.
     */
    public function toggleAvailability(MenuItem $menuItem): JsonResponse
    {
        $this->authorize('toggleAvailability', $menuItem);

        $menuItem = $this->menuItemService->toggleAvailability($menuItem);

        return response()->json([
            'message' => 'Menu item availability toggled successfully.',
            'data' => new MenuItemResource($menuItem->load('restaurant')),
        ]);
    }
}
