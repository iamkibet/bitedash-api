<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MenuItem;
use App\Models\Restaurant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MenuItemService
{
    /**
     * Get all menu items for a restaurant.
     */
    public function getByRestaurant(int $restaurantId, ?bool $isAvailable = null): LengthAwarePaginator
    {
        $query = MenuItem::where('restaurant_id', $restaurantId);

        if ($isAvailable !== null) {
            $query->where('is_available', $isAvailable);
        }

        return $query->paginate(20);
    }

    /**
     * Get a single menu item by ID.
     */
    public function getById(int $id): ?MenuItem
    {
        return MenuItem::with('restaurant')->find($id);
    }

    /**
     * Create a new menu item.
     */
    public function create(Restaurant $restaurant, array $data, ?UploadedFile $imageFile = null): MenuItem
    {
        $imageUrl = $data['image_url'] ?? null;

        // Handle image file upload
        if ($imageFile) {
            // Store the image and get the path (e.g., "menu-items/filename.jpg")
            $imagePath = $imageFile->store('menu-items', 'public');
            // Store just the path, not the full URL - the resource will generate the URL
            $imageUrl = $imagePath;
        }

        return MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'image_url' => $imageUrl,
            'is_available' => $data['is_available'] ?? true,
        ]);
    }

    /**
     * Update a menu item.
     */
    public function update(MenuItem $menuItem, array $data, ?UploadedFile $imageFile = null): MenuItem
    {
        $imageUrl = $data['image_url'] ?? $menuItem->image_url;

        // Handle image file upload
        if ($imageFile) {
            // Delete old image if it exists and is stored locally
            if ($menuItem->image_url && !filter_var($menuItem->image_url, FILTER_VALIDATE_URL)) {
                // The image_url might be a path like "menu-items/filename.jpg" or a full URL
                // If it's a path, delete it
                if (Storage::disk('public')->exists($menuItem->image_url)) {
                    Storage::disk('public')->delete($menuItem->image_url);
                }
            }

            // Store new image and get the path (e.g., "menu-items/filename.jpg")
            $imagePath = $imageFile->store('menu-items', 'public');
            // Store just the path, not the full URL - the resource will generate the URL
            $imageUrl = $imagePath;
        }

        $menuItem->update([
            'name' => $data['name'] ?? $menuItem->name,
            'description' => $data['description'] ?? $menuItem->description,
            'price' => $data['price'] ?? $menuItem->price,
            'image_url' => $imageUrl,
            'is_available' => $data['is_available'] ?? $menuItem->is_available,
        ]);

        return $menuItem->fresh();
    }

    /**
     * Toggle menu item availability.
     */
    public function toggleAvailability(MenuItem $menuItem): MenuItem
    {
        $menuItem->update([
            'is_available' => !$menuItem->is_available,
        ]);

        return $menuItem->fresh();
    }

    /**
     * Delete a menu item (soft delete).
     */
    public function delete(MenuItem $menuItem): bool
    {
        return $menuItem->delete();
    }
}
