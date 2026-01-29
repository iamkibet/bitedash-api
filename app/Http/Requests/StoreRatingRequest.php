<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRatingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isCustomer() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'menu_item_id' => ['required', 'integer', 'exists:menu_items,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->user();
            $menuItemId = $this->input('menu_item_id');

            if ($user && $menuItemId) {
                // Check if customer has ordered and paid for this menu item
                $hasOrderedAndPaid = \App\Models\OrderItem::whereHas('order', function ($query) use ($user) {
                    $query->where('customer_id', $user->id)
                        ->where('payment_status', \App\Models\Order::PAYMENT_STATUS_PAID);
                })
                    ->where('menu_item_id', $menuItemId)
                    ->exists();

                if (!$hasOrderedAndPaid) {
                    $validator->errors()->add(
                        'menu_item_id',
                        'You can only rate menu items that you have ordered and paid for.'
                    );
                }
            }
        });
    }
}
