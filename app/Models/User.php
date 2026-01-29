<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'role',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Hash the password using the default hashing driver (Argon2id) before saving.
     */
    protected function setPasswordAttribute(string $value): void
    {
        $this->attributes['password'] = Hash::make($value); // Uses default driver (argon2id)
    }

    /**
     * Get the restaurants owned by this user.
     */
    public function restaurants(): HasMany
    {
        return $this->hasMany(Restaurant::class, 'owner_id');
    }

    /**
     * Get the orders placed by this user as a customer.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    /**
     * Get the orders assigned to this user as a rider.
     */
    public function riderOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'rider_id');
    }

    /**
     * Get the menu items favourited by this user.
     */
    public function favourites(): HasMany
    {
        return $this->hasMany(Favourite::class);
    }

    /**
     * Get the menu items favourited by this user (many-to-many).
     */
    public function favouriteMenuItems(): BelongsToMany
    {
        return $this->belongsToMany(MenuItem::class, 'favourites')
            ->withTimestamps();
    }

    /**
     * Get the ratings created by this user.
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if user is a restaurant owner.
     */
    public function isRestaurant(): bool
    {
        return $this->hasRole('restaurant');
    }

    /**
     * Check if user is a rider.
     */
    public function isRider(): bool
    {
        return $this->hasRole('rider');
    }

    /**
     * Check if user is a customer.
     */
    public function isCustomer(): bool
    {
        return $this->hasRole('customer');
    }
}
