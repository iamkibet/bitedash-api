<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Restaurant;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Order::class);

        $orders = $this->orderService->getAllForUser($request->user());

        return response()->json([
            'data' => OrderResource::collection($orders->items()),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            $restaurant = Restaurant::findOrFail($request->validated('restaurant_id'));

            $order = $this->orderService->create(
                $request->user(),
                $restaurant,
                $request->validated('items'),
                $request->validated('delivery_address'),
                $request->validated('notes')
            );

            return response()->json([
                'message' => 'Order created successfully.',
                'data' => new OrderResource($order),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Order creation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Order creation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Order creation failed. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred during order creation.',
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        $order = $this->orderService->getById($order->id);

        return response()->json([
            'data' => new OrderResource($order),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        try {
            // Update status if provided
            if ($request->has('status')) {
                $order = $this->orderService->updateStatus($order, $request->validated('status'));
            }

            // Assign rider if provided (restaurant managers can assign riders)
            if ($request->has('rider_id')) {
                $riderId = $request->validated('rider_id');
                
                // If rider_id is null, unassign rider
                if ($riderId === null) {
                    $order->update(['rider_id' => null]);
                } else {
                    // Validate rider exists and is a rider
                    $rider = \App\Models\User::find($riderId);
                    if (!$rider || !$rider->isRider()) {
                        return response()->json([
                            'message' => 'Invalid rider. User must be a rider.',
                            'errors' => ['rider_id' => ['The selected rider is invalid.']],
                        ], 422);
                    }
                    
                    // Use the service method to assign rider (includes validations)
                    $order = $this->orderService->assignRider($order, $rider);
                    
                    // Auto-update status to on_the_way when rider is assigned (best-effort)
                    if ($order->status === Order::STATUS_PREPARING && $order->canTransitionTo(Order::STATUS_ON_THE_WAY)) {
                        try {
                            $order = $this->orderService->updateStatus($order, Order::STATUS_ON_THE_WAY);
                        } catch (\Throwable $e) {
                            // Rider already assigned; avoid 422. Log and continue.
                            report($e);
                        }
                    }
                }
            }

            // Update notes if provided
            if ($request->has('notes')) {
                $order->update(['notes' => $request->validated('notes')]);
                $order->refresh();
            }

            return response()->json([
                'message' => 'Order updated successfully.',
                'data' => new OrderResource($order->load(['customer', 'restaurant', 'rider', 'orderItems.menuItem'])),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Order update failed.',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order): JsonResponse
    {
        $this->authorize('delete', $order);

        $this->orderService->delete($order);

        return response()->json([
            'message' => 'Order deleted successfully.',
        ]);
    }

    /**
     * Cancel an order.
     */
    public function cancel(Request $request, Order $order): JsonResponse
    {
        $this->authorize('cancel', $order);

        try {
            $order = $this->orderService->cancel($order, $request->input('reason'));

            return response()->json([
                'message' => 'Order cancelled successfully.',
                'data' => new OrderResource($order->load(['customer', 'restaurant', 'rider', 'orderItems.menuItem'])),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Order cancellation failed.',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Get available orders for riders (preparing status, no rider assigned).
     */
    public function availableForRiders(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Order::class);

        if (!$request->user()->isRider()) {
            return response()->json([
                'message' => 'Only riders can view available orders.',
            ], 403);
        }

        $orders = $this->orderService->getAvailableForRiders();

        return response()->json([
            'data' => OrderResource::collection($orders),
        ]);
    }

    /**
     * Get orders assigned to the authenticated rider.
     */
    public function myRiderOrders(Request $request): JsonResponse
    {
        if (!$request->user()->isRider()) {
            return response()->json([
                'message' => 'Only riders can access this endpoint.',
            ], 403);
        }

        $orders = $this->orderService->getOrdersForRider($request->user());

        return response()->json([
            'data' => OrderResource::collection($orders->items()),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    /**
     * Accept an order (assign rider - self-assignment).
     */
    public function accept(Request $request, Order $order): JsonResponse
    {
        $this->authorize('accept', $order);

        try {
            $order = $this->orderService->assignRider($order, $request->user());
            
            // Auto-update status to on_the_way when rider accepts
            if ($order->status === Order::STATUS_PREPARING && $order->canTransitionTo(Order::STATUS_ON_THE_WAY)) {
                $order = $this->orderService->updateStatus($order, Order::STATUS_ON_THE_WAY);
            }

            return response()->json([
                'message' => 'Order accepted successfully.',
                'data' => new OrderResource($order->load(['customer', 'restaurant', 'rider', 'orderItems.menuItem'])),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Order acceptance failed.',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Get pending orders for a restaurant.
     */
    public function pendingForRestaurant(Request $request, Restaurant $restaurant): JsonResponse
    {
        $this->authorize('view', $restaurant);

        if ($request->user()->id !== $restaurant->owner_id && !$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Unauthorized to view orders for this restaurant.',
            ], 403);
        }

        $orders = $this->orderService->getPendingForRestaurant($restaurant);

        return response()->json([
            'data' => OrderResource::collection($orders),
        ]);
    }

    /**
     * Get all orders for the authenticated restaurant owner's restaurant.
     */
    public function myRestaurantOrders(Request $request): JsonResponse
    {
        if (!$request->user()->isRestaurant()) {
            return response()->json([
                'message' => 'Only restaurant owners can access this endpoint.',
            ], 403);
        }

        $orders = $this->orderService->getAllForRestaurantOwner($request->user());

        return response()->json([
            'data' => OrderResource::collection($orders->items()),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    /**
     * Get all orders for a specific restaurant.
     */
    public function restaurantOrders(Request $request, Restaurant $restaurant): JsonResponse
    {
        $this->authorize('view', $restaurant);

        if ($request->user()->id !== $restaurant->owner_id && !$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Unauthorized to view orders for this restaurant.',
            ], 403);
        }

        $orders = $this->orderService->getAllForRestaurant($restaurant);

        return response()->json([
            'data' => OrderResource::collection($orders->items()),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }
}
