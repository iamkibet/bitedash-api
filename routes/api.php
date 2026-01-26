<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\MenuItemController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\RestaurantController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::prefix('v1')->group(function (): void {
    // Authentication routes
    Route::post('/register', [AuthController::class, 'register'])->name('api.v1.register');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1') // 5 attempts per minute
        ->name('api.v1.login');

    // Paystack webhook (must be public, signature-verified)
    Route::post('/payments/webhook', [PaymentController::class, 'webhook'])
        ->name('api.v1.payments.webhook');
});

// Protected routes
Route::prefix('v1')->middleware('auth:sanctum')->group(function (): void {
    // Authentication routes
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.v1.logout');
    Route::get('/me', [AuthController::class, 'me'])->name('api.v1.me');
    Route::get('/user', [AuthController::class, 'me'])->name('api.v1.user'); // Alias for frontend compatibility

    // User routes
    Route::get('/users', [UserController::class, 'index'])->name('api.v1.users.index');
    Route::get('/riders', [UserController::class, 'riders'])->name('api.v1.riders');

    // Store routes (formerly restaurants)
    Route::get('/stores', [RestaurantController::class, 'index'])->name('api.v1.stores.index');
    Route::get('/stores/my-store', [RestaurantController::class, 'myStore'])
        ->middleware('role:restaurant')
        ->name('api.v1.stores.my-store');
    Route::post('/stores', [RestaurantController::class, 'store'])
        ->middleware('role:restaurant')
        ->name('api.v1.stores.store');
    Route::get('/stores/{restaurant}', [RestaurantController::class, 'show'])->name('api.v1.stores.show');
    Route::put('/stores/{restaurant}', [RestaurantController::class, 'update'])->name('api.v1.stores.update');
    Route::patch('/stores/{restaurant}', [RestaurantController::class, 'update'])->name('api.v1.stores.patch');
    Route::delete('/stores/{restaurant}', [RestaurantController::class, 'destroy'])->name('api.v1.stores.destroy');
    Route::post('/stores/{restaurant}/toggle-status', [RestaurantController::class, 'toggleStatus'])
        ->middleware('role:restaurant')
        ->name('api.v1.stores.toggle-status');

    // Backward compatibility routes (deprecated - use /stores instead)
    Route::get('/restaurants', [RestaurantController::class, 'index'])->name('api.v1.restaurants.index');
    Route::get('/restaurants/my-store', [RestaurantController::class, 'myStore'])
        ->middleware('role:restaurant')
        ->name('api.v1.restaurants.my-store');
    Route::post('/restaurants', [RestaurantController::class, 'store'])
        ->middleware('role:restaurant')
        ->name('api.v1.restaurants.store');
    Route::get('/restaurants/{restaurant}', [RestaurantController::class, 'show'])->name('api.v1.restaurants.show');
    Route::put('/restaurants/{restaurant}', [RestaurantController::class, 'update'])->name('api.v1.restaurants.update');
    Route::patch('/restaurants/{restaurant}', [RestaurantController::class, 'update'])->name('api.v1.restaurants.patch');
    Route::delete('/restaurants/{restaurant}', [RestaurantController::class, 'destroy'])->name('api.v1.restaurants.destroy');
    Route::post('/restaurants/{restaurant}/toggle-status', [RestaurantController::class, 'toggleStatus'])
        ->middleware('role:restaurant')
        ->name('api.v1.restaurants.toggle-status');

    // Menu Item routes (nested under stores)
    Route::get('/stores/{restaurant}/menu-items', [MenuItemController::class, 'index'])->name('api.v1.menu-items.index');
    // Backward compatibility
    Route::get('/restaurants/{restaurant}/menu-items', [MenuItemController::class, 'index'])->name('api.v1.restaurants.menu-items.index');
    Route::get('/menu-items/my-restaurant', [MenuItemController::class, 'myMenuItems'])
        ->middleware('role:restaurant')
        ->name('api.v1.menu-items.my-restaurant');
    Route::post('/menu-items', [MenuItemController::class, 'store'])
        ->middleware('role:restaurant')
        ->name('api.v1.menu-items.store');
    Route::get('/menu-items/{menuItem}', [MenuItemController::class, 'show'])->name('api.v1.menu-items.show');
    Route::put('/menu-items/{menuItem}', [MenuItemController::class, 'update'])->name('api.v1.menu-items.update');
    Route::patch('/menu-items/{menuItem}', [MenuItemController::class, 'update'])->name('api.v1.menu-items.patch');
    Route::delete('/menu-items/{menuItem}', [MenuItemController::class, 'destroy'])->name('api.v1.menu-items.destroy');
    Route::post('/menu-items/{menuItem}/toggle-availability', [MenuItemController::class, 'toggleAvailability'])
        ->middleware('role:restaurant')
        ->name('api.v1.menu-items.toggle-availability');

    // Order routes
    Route::get('/orders', [OrderController::class, 'index'])->name('api.v1.orders.index');
    Route::post('/orders', [OrderController::class, 'store'])
        ->middleware('role:customer')
        ->name('api.v1.orders.store');
    Route::get('/orders/available', [OrderController::class, 'availableForRiders'])
        ->middleware('role:rider')
        ->name('api.v1.orders.available');
    Route::get('/orders/my-rider', [OrderController::class, 'myRiderOrders'])
        ->middleware('role:rider')
        ->name('api.v1.orders.my-rider');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('api.v1.orders.show');
    Route::put('/orders/{order}', [OrderController::class, 'update'])->name('api.v1.orders.update');
    Route::patch('/orders/{order}', [OrderController::class, 'update'])->name('api.v1.orders.patch');
    Route::delete('/orders/{order}', [OrderController::class, 'destroy'])->name('api.v1.orders.destroy');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('api.v1.orders.cancel');
    Route::post('/orders/{order}/accept', [OrderController::class, 'accept'])
        ->middleware('role:rider')
        ->name('api.v1.orders.accept');
    Route::get('/orders/my-restaurant', [OrderController::class, 'myRestaurantOrders'])
        ->middleware('role:restaurant')
        ->name('api.v1.orders.my-restaurant');
    Route::get('/stores/{restaurant}/orders', [OrderController::class, 'restaurantOrders'])
        ->middleware('role:restaurant')
        ->name('api.v1.stores.orders');
    Route::get('/stores/{restaurant}/orders/pending', [OrderController::class, 'pendingForRestaurant'])
        ->middleware('role:restaurant')
        ->name('api.v1.stores.orders.pending');
    // Backward compatibility
    Route::get('/restaurants/{restaurant}/orders', [OrderController::class, 'restaurantOrders'])
        ->middleware('role:restaurant')
        ->name('api.v1.restaurants.orders');
    Route::get('/restaurants/{restaurant}/orders/pending', [OrderController::class, 'pendingForRestaurant'])
        ->middleware('role:restaurant')
        ->name('api.v1.restaurants.orders.pending');

    // Payment routes
    Route::post('/orders/{order}/payments/initiate', [PaymentController::class, 'initiate'])
        ->middleware('role:customer')
        ->name('api.v1.payments.initiate');
    Route::get('/payments/{reference}/verify', [PaymentController::class, 'verify'])
        ->name('api.v1.payments.verify');
});
