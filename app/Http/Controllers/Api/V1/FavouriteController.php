<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFavouriteRequest;
use App\Http\Resources\FavouriteResource;
use App\Http\Resources\MenuItemResource;
use App\Models\Favourite;
use App\Models\MenuItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavouriteController extends Controller
{
    /**
     * Display a listing of the user's favourites.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $favourites = Favourite::with('menuItem.restaurant')
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(15);

        return response()->json([
            'data' => FavouriteResource::collection($favourites->items()),
            'meta' => [
                'current_page' => $favourites->currentPage(),
                'last_page' => $favourites->lastPage(),
                'per_page' => $favourites->perPage(),
                'total' => $favourites->total(),
            ],
        ]);
    }

    /**
     * Store a newly created favourite.
     */
    public function store(StoreFavouriteRequest $request): JsonResponse
    {
        $user = $request->user();
        $menuItemId = $request->validated('menu_item_id');

        // Check if already favourited
        $existingFavourite = Favourite::where('user_id', $user->id)
            ->where('menu_item_id', $menuItemId)
            ->first();

        if ($existingFavourite) {
            return response()->json([
                'message' => 'Menu item is already in your favourites.',
                'data' => new FavouriteResource($existingFavourite),
            ], 200);
        }

        $favourite = Favourite::create([
            'user_id' => $user->id,
            'menu_item_id' => $menuItemId,
        ]);

        $favourite->load('menuItem.restaurant');

        return response()->json([
            'message' => 'Menu item added to favourites successfully.',
            'data' => new FavouriteResource($favourite),
        ], 201);
    }

    /**
     * Remove the specified favourite.
     */
    public function destroy(Request $request, MenuItem $menuItem): JsonResponse
    {
        $user = $request->user();

        $favourite = Favourite::where('user_id', $user->id)
            ->where('menu_item_id', $menuItem->id)
            ->first();

        if (!$favourite) {
            return response()->json([
                'message' => 'Menu item is not in your favourites.',
            ], 404);
        }

        $favourite->delete();

        return response()->json([
            'message' => 'Menu item removed from favourites successfully.',
        ], 200);
    }
}
