<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRatingRequest;
use App\Http\Requests\UpdateRatingRequest;
use App\Http\Resources\RatingResource;
use App\Models\MenuItem;
use App\Models\Rating;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    /**
     * Display a listing of ratings for a menu item.
     */
    public function index(Request $request, MenuItem $menuItem): JsonResponse
    {
        $ratings = Rating::with('user')
            ->where('menu_item_id', $menuItem->id)
            ->latest()
            ->paginate(15);

        $averageRating = $menuItem->ratings()->avg('rating');
        $averageRating = $averageRating !== null ? (float) $averageRating : 0.0;

        return response()->json([
            'data' => RatingResource::collection($ratings->items()),
            'meta' => [
                'current_page' => $ratings->currentPage(),
                'last_page' => $ratings->lastPage(),
                'per_page' => $ratings->perPage(),
                'total' => $ratings->total(),
                'average_rating' => round($averageRating, 2),
                'total_ratings' => $menuItem->ratings()->count(),
            ],
        ]);
    }

    /**
     * Store a newly created rating.
     */
    public function store(StoreRatingRequest $request): JsonResponse
    {
        $user = $request->user();
        $menuItemId = $request->validated('menu_item_id');

        // Check if already rated
        $existingRating = Rating::where('user_id', $user->id)
            ->where('menu_item_id', $menuItemId)
            ->first();

        if ($existingRating) {
            return response()->json([
                'message' => 'You have already rated this menu item. Use update to modify your rating.',
                'data' => new RatingResource($existingRating),
            ], 422);
        }

        $rating = Rating::create([
            'user_id' => $user->id,
            'menu_item_id' => $menuItemId,
            'rating' => $request->validated('rating'),
            'comment' => $request->validated('comment'),
        ]);

        $rating->load('user', 'menuItem');

        return response()->json([
            'message' => 'Rating created successfully.',
            'data' => new RatingResource($rating),
        ], 201);
    }

    /**
     * Update the specified rating.
     */
    public function update(UpdateRatingRequest $request, Rating $rating): JsonResponse
    {
        $user = $request->user();

        // Check if user owns the rating
        if ($rating->user_id !== $user->id) {
            return response()->json([
                'message' => 'You can only update your own ratings.',
            ], 403);
        }

        $rating->update($request->validated());
        $rating->load('user', 'menuItem');

        return response()->json([
            'message' => 'Rating updated successfully.',
            'data' => new RatingResource($rating),
        ], 200);
    }

    /**
     * Remove the specified rating.
     */
    public function destroy(Request $request, Rating $rating): JsonResponse
    {
        $user = $request->user();

        // Check if user owns the rating
        if ($rating->user_id !== $user->id) {
            return response()->json([
                'message' => 'You can only delete your own ratings.',
            ], 403);
        }

        $rating->delete();

        return response()->json([
            'message' => 'Rating deleted successfully.',
        ], 200);
    }
}
