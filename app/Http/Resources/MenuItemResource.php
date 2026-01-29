<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class MenuItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Get full image URL - handle both uploaded files and external URLs
        $imageUrl = $this->image_url;
        if ($imageUrl) {
            // Check if it's already a full URL (external image or already processed)
            if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                // It's an external URL, use it as is
                // No transformation needed
            } elseif (str_starts_with($imageUrl, '/storage/')) {
                // It's already a storage URL path (like "/storage/menu-items/filename.jpg")
                // Just prepend the base URL
                $baseUrl = rtrim(config('app.url'), '/');
                $imageUrl = $baseUrl . $imageUrl;
            } else {
                // It's a storage path (like "menu-items/filename.jpg")
                // Generate the full accessible URL
                // The storage link makes /storage point to storage/app/public
                $baseUrl = rtrim(config('app.url'), '/');
                $imageUrl = $baseUrl . '/storage/' . $imageUrl;
            }
        }
        // If no image is set, $imageUrl will be null - frontend can handle placeholder

        // Get authenticated user's rating if available (from eager loaded relationship)
        $userRating = null;
        if ($request->user() && $this->relationLoaded('ratings')) {
            // The ratings relationship is filtered to only include the user's rating
            $userRating = $this->ratings->first();
        }

        // Get average rating and count from eager loaded attributes
        $averageRating = $this->ratings_avg_rating ?? $this->average_rating;
        $ratingsCount = $this->ratings_count ?? 0;

        return [
            'id' => $this->id,
            'restaurant_id' => $this->restaurant_id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => (float) $this->price,
            'image_url' => $imageUrl,
            'is_available' => $this->is_available,
            'restaurant' => new RestaurantResource($this->whenLoaded('restaurant')),
            'ratings' => [
                'average' => $averageRating !== null ? round((float) $averageRating, 2) : null,
                'count' => $ratingsCount,
                'user_rating' => $userRating ? [
                    'id' => $userRating->id,
                    'rating' => $userRating->rating,
                    'comment' => $userRating->comment,
                    'created_at' => $userRating->created_at?->toIso8601String(),
                    'updated_at' => $userRating->updated_at?->toIso8601String(),
                ] : null,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
