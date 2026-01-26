<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Calculate total from order items if total_amount is missing or invalid
        $totalAmount = $this->total_amount ? (float) $this->total_amount : 0.0;
        
        // If total is 0 or missing, calculate from order items
        if ($totalAmount <= 0 && $this->relationLoaded('orderItems')) {
            $calculatedTotal = $this->orderItems->sum(function ($item) {
                $unitPrice = (float) ($item->unit_price ?? $item->menuItem->price ?? 0);
                $quantity = (int) ($item->quantity ?? 0);
                return $unitPrice * $quantity;
            });
            
            if ($calculatedTotal > 0) {
                $totalAmount = $calculatedTotal;
            }
        }
        
        return [
            'id' => $this->id,
            'customer' => new UserResource($this->whenLoaded('customer')),
            'restaurant' => new RestaurantResource($this->whenLoaded('restaurant')),
            'rider' => new UserResource($this->whenLoaded('rider')),
            'total_amount' => $totalAmount,
            'total' => $totalAmount, // Alias for frontend compatibility
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'delivery_address' => $this->delivery_address,
            'notes' => $this->notes,
            'order_items' => OrderItemResource::collection($this->whenLoaded('orderItems')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
