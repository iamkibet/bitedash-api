<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('order'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $order = $this->route('order');

        return [
            'status' => [
                'sometimes',
                'string',
                Rule::in([
                    Order::STATUS_PENDING,
                    Order::STATUS_PREPARING,
                    Order::STATUS_ON_THE_WAY,
                    Order::STATUS_DELIVERED,
                    Order::STATUS_CANCELLED,
                ]),
                function ($attribute, $value, $fail) use ($order) {
                    if ($order && !$order->canTransitionTo($value)) {
                        $fail('Invalid status transition from ' . $order->status . ' to ' . $value . '.');
                    }
                },
            ],
            'rider_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
