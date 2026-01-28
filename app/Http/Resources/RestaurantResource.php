<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class RestaurantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Generate full URL for image if it's a local path
        $imageUrl = $this->image_url;
        if ($imageUrl && !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            // It's a local path, generate the full URL
            $imageUrl = Storage::disk('public')->url($imageUrl);
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'image_url' => $imageUrl,
            'location' => $this->location,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'is_open' => $this->is_open,
            'owner' => new UserResource($this->whenLoaded('owner')),
            'menu_items' => MenuItemResource::collection($this->whenLoaded('menuItems')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
