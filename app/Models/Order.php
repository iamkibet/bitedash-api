<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Order status constants.
     */
    public const string STATUS_PENDING = 'pending';
    public const string STATUS_PREPARING = 'preparing';
    public const string STATUS_ON_THE_WAY = 'on_the_way';
    public const string STATUS_DELIVERED = 'delivered';
    public const string STATUS_CANCELLED = 'cancelled';

    /**
     * Payment status constants.
     */
    public const string PAYMENT_STATUS_UNPAID = 'unpaid';
    public const string PAYMENT_STATUS_PAID = 'paid';
    public const string PAYMENT_STATUS_FAILED = 'failed';

    /**
     * Valid status transitions.
     */
    private const array VALID_STATUS_TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_PREPARING, self::STATUS_CANCELLED],
        self::STATUS_PREPARING => [self::STATUS_ON_THE_WAY, self::STATUS_CANCELLED],
        self::STATUS_ON_THE_WAY => [self::STATUS_DELIVERED, self::STATUS_CANCELLED],
        self::STATUS_DELIVERED => [], // Final state
        self::STATUS_CANCELLED => [], // Final state
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'customer_id',
        'restaurant_id',
        'rider_id',
        'total_amount',
        'status',
        'payment_status',
        'delivery_address',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the customer who placed the order.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Get the restaurant for the order.
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Get the rider assigned to the order.
     */
    public function rider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rider_id');
    }

    /**
     * Get the order items for the order.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the menu items for the order.
     */
    public function menuItems(): BelongsToMany
    {
        return $this->belongsToMany(MenuItem::class, 'order_items')
            ->withPivot('quantity', 'unit_price')
            ->withTimestamps();
    }

    /**
     * Get the payments for the order.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Check if a status transition is valid.
     */
    public function canTransitionTo(string $newStatus): bool
    {
        if (!isset(self::VALID_STATUS_TRANSITIONS[$this->status])) {
            return false;
        }

        return in_array($newStatus, self::VALID_STATUS_TRANSITIONS[$this->status], true);
    }

    /**
     * Check if order is in a final state.
     */
    public function isFinalState(): bool
    {
        return in_array($this->status, [self::STATUS_DELIVERED, self::STATUS_CANCELLED], true);
    }

    /**
     * Check if order is paid.
     */
    public function isPaid(): bool
    {
        return $this->payment_status === self::PAYMENT_STATUS_PAID;
    }
}
