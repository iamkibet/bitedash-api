<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MenuItem extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'restaurant_id',
        'name',
        'description',
        'price',
        'image_url',
        'is_available',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_available' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the restaurant that owns the menu item.
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Get the orders that include this menu item.
     */
    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'order_items')
            ->withPivot('quantity', 'unit_price')
            ->withTimestamps();
    }

    /**
     * Get the favourites for this menu item.
     */
    public function favourites(): HasMany
    {
        return $this->hasMany(Favourite::class);
    }

    /**
     * Get the users who favourited this menu item (many-to-many).
     */
    public function favouritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favourites')
            ->withTimestamps();
    }

    /**
     * Get the ratings for this menu item.
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    /**
     * Get the average rating for this menu item.
     */
    public function getAverageRatingAttribute(): ?float
    {
        $avg = $this->ratings()->avg('rating');
        return $avg !== null ? (float) $avg : null;
    }

    /**
     * Get the total number of ratings for this menu item.
     */
    public function getRatingsCountAttribute(): int
    {
        return $this->ratings()->count();
    }

    /**
     * Get the rating given by a specific user for this menu item.
     */
    public function getUserRating(?int $userId): ?Rating
    {
        if (!$userId) {
            return null;
        }

        return $this->ratings()->where('user_id', $userId)->first();
    }
}
