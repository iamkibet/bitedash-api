<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitiatePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'phone_number' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    // Remove spaces, dashes, and other formatting
                    $cleaned = preg_replace('/[\s\-\(\)]/', '', $value);
                    
                    // Check if it matches Kenyan phone number patterns
                    // +254XXXXXXXXX (13 chars: +254 + 9 digits)
                    // 254XXXXXXXXX (12 chars: 254 + 9 digits)
                    // 0XXXXXXXXX (10 chars: 0 + 9 digits)
                    // 07XXXXXXXX (10 chars: 07 + 8 digits) - also valid
                    
                    $patterns = [
                        '/^\+254[0-9]{9}$/',      // +254712345678
                        '/^254[0-9]{9}$/',        // 254712345678
                        '/^0[0-9]{9}$/',          // 0712345678
                    ];
                    
                    $matched = false;
                    foreach ($patterns as $pattern) {
                        if (preg_match($pattern, $cleaned)) {
                            $matched = true;
                            break;
                        }
                    }
                    
                    if (!$matched) {
                        $fail('Invalid phone number format. Please use: +254XXXXXXXXX, 254XXXXXXXXX, or 0XXXXXXXXX (9 digits after prefix).');
                    }
                },
            ],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Clean the phone number before validation
        if ($this->has('phone_number')) {
            $this->merge([
                'phone_number' => preg_replace('/[\s\-\(\)]/', '', $this->input('phone_number')),
            ]);
        }
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone_number.required' => 'Phone number is required.',
            'phone_number.string' => 'Phone number must be a string.',
        ];
    }
}
