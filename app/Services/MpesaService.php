<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MpesaService
{
    private string $secretKey;
    private string $publicKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->secretKey = config('services.paystack.secret_key', 'test_secret_key');
        $this->publicKey = config('services.paystack.public_key', 'test_public_key');
        $this->baseUrl = config('services.paystack.base_url', 'https://api.paystack.co');
    }

    /**
     * Initiate M-Pesa payment via Paystack Charge API.
     *
     * @return array<string, mixed>
     */
    public function initiateCharge(Order $order, string $phoneNumber, string $email): array
    {
        $reference = $this->generateTransactionReference();

        try {
            // Format phone number for Paystack (must be 254XXXXXXXXX - exactly 12 digits)
            $formattedPhone = $this->formatPhoneNumber($phoneNumber);
            
            // Validate formatted phone number - Paystack requires exactly 12 digits starting with 254
            if (strlen($formattedPhone) !== 12 || !str_starts_with($formattedPhone, '254') || !ctype_digit($formattedPhone)) {
                Log::error('Invalid phone number format after formatting', [
                    'original' => $phoneNumber,
                    'formatted' => $formattedPhone,
                    'length' => strlen($formattedPhone),
                    'is_digits' => ctype_digit($formattedPhone),
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Invalid phone number format. Please use: 254XXXXXXXXX or 0XXXXXXXXX (9 digits after prefix). Received: ' . $phoneNumber,
                    'reference' => $reference,
                ];
            }
            
            // Validate amount
            $amount = (float) $order->total_amount;
            if ($amount <= 0) {
                return [
                    'success' => false,
                    'message' => 'Invalid order amount.',
                    'reference' => $reference,
                ];
            }
            
            $amountInKobo = (int) ($amount * 100);
            
            Log::info('Payment initiation', [
                'order_id' => $order->id,
                'original_phone' => $phoneNumber,
                'formatted_phone' => $formattedPhone,
                'amount' => $amount,
                'amount_in_kobo' => $amountInKobo,
            ]);

            // Paystack M-Pesa phone number format
            // Paystack may require: +254XXXXXXXXX (with + prefix) or 254XXXXXXXXX (without +)
            // Note: M-Pesa must be enabled in Paystack dashboard (Settings > Preferences > Mobile Money)
            // Try with + prefix first as some Paystack implementations require it
            $phoneWithPlus = '+' . $formattedPhone;
            
            $requestPayload = [
                'email' => $email,
                'amount' => $amountInKobo,
                'reference' => $reference,
                'currency' => 'KES',
                'mobile_money' => [
                    'phone' => $phoneWithPlus, // Try +254XXXXXXXXX format first
                    'provider' => 'mpesa',
                ],
                'metadata' => [
                    'order_id' => $order->id,
                    'customer_id' => $order->customer_id,
                    'restaurant_id' => $order->restaurant_id,
                ],
            ];
            
            Log::info('Paystack request payload', [
                'phone' => $requestPayload['mobile_money']['phone'],
                'phone_type' => gettype($requestPayload['mobile_money']['phone']),
                'phone_length' => strlen($requestPayload['mobile_money']['phone']),
                'amount' => $amountInKobo,
                'base_url' => $this->baseUrl,
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/charge", $requestPayload);

            $responseData = $response->json();
            $isSuccessful = $response->successful();

            // If first attempt failed with phone error, try alternative format
            if (!$isSuccessful) {
                $errorMessage = $responseData['message'] ?? 'Payment initiation failed';
                
                // Check if it's a phone number format error from Paystack
                $isPhoneError = isset($responseData['meta']['nextStep']) || 
                               (isset($responseData['message']) && 
                                (str_contains(strtolower($errorMessage), 'phone') || 
                                 str_contains(strtolower($errorMessage), 'invalid')));
                
                if ($isPhoneError) {
                    // Try alternative formats:
                    // 1. Without + prefix (254XXXXXXXXX)
                    // 2. Without country code (XXXXXXXXX)
                    $phoneWithoutPlus = $formattedPhone; // 254XXXXXXXXX
                    $phoneWithoutCountryCode = substr($formattedPhone, 3); // XXXXXXXXX
                    
                    Log::info('Trying alternative phone formats', [
                        'original_with_plus' => $phoneWithPlus,
                        'without_plus' => $phoneWithoutPlus,
                        'without_country_code' => $phoneWithoutCountryCode,
                    ]);
                    
                    // Retry 1: Without + prefix
                    $retryPayload1 = $requestPayload;
                    $retryPayload1['mobile_money']['phone'] = $phoneWithoutPlus;
                    
                    $retryResponse1 = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $this->secretKey,
                        'Content-Type' => 'application/json',
                    ])->post("{$this->baseUrl}/charge", $retryPayload1);
                    
                    $retryResponseData1 = $retryResponse1->json();
                    $retryIsSuccessful1 = $retryResponse1->successful();
                    
                    if ($retryIsSuccessful1) {
                        Log::info('Payment succeeded without + prefix', [
                            'phone_format' => 'without_plus',
                            'phone' => $phoneWithoutPlus,
                        ]);
                        $responseData = $retryResponseData1;
                        $isSuccessful = true;
                        $formattedPhone = $phoneWithoutPlus;
                    } else {
                        // Retry 2: Without country code
                        $retryPayload2 = $requestPayload;
                        $retryPayload2['mobile_money']['phone'] = $phoneWithoutCountryCode;
                        
                        $retryResponse2 = Http::withHeaders([
                            'Authorization' => 'Bearer ' . $this->secretKey,
                            'Content-Type' => 'application/json',
                        ])->post("{$this->baseUrl}/charge", $retryPayload2);
                        
                        $retryResponseData2 = $retryResponse2->json();
                        $retryIsSuccessful2 = $retryResponse2->successful();
                        
                        if ($retryIsSuccessful2) {
                            // Success with alternative format - use retry response
                            Log::info('Payment succeeded without country code', [
                                'phone_format' => 'without_country_code',
                                'phone' => $phoneWithoutCountryCode,
                            ]);
                            
                            $responseData = $retryResponseData2;
                            $isSuccessful = true;
                            $formattedPhone = $phoneWithoutCountryCode; // Update for payment record
                        } else {
                            // All formats failed - this is likely a Paystack account configuration issue
                            $errorMessage = 'Paystack rejected all phone number formats. This indicates a Paystack account configuration issue. Please: 1) Log into your Paystack dashboard, 2) Go to Settings > Preferences > Mobile Money, 3) Ensure M-Pesa is enabled and activated, 4) Verify your API keys have M-Pesa permissions. Phone number received: ' . $phoneNumber;
                            
                            Log::error('Paystack Charge API failed with all phone formats', [
                                'order_id' => $order->id,
                                'reference' => $reference,
                                'formatted_phone_with_plus' => $phoneWithPlus,
                                'formatted_phone_without_plus' => $phoneWithoutPlus,
                                'formatted_phone_without_code' => $phoneWithoutCountryCode,
                                'original_phone' => $phoneNumber,
                                'first_response' => $responseData,
                                'retry_response_1' => $retryResponseData1,
                                'retry_response_2' => $retryResponseData2,
                                'paystack_error_code' => $responseData['code'] ?? null,
                                'paystack_error_type' => $responseData['type'] ?? null,
                            ]);

                            return [
                                'success' => false,
                                'message' => $errorMessage,
                                'reference' => $reference,
                                'paystack_error' => $responseData,
                            ];
                        }
                    }
                } else {
                    // Non-phone error
                    Log::error('Paystack Charge API failed', [
                        'order_id' => $order->id,
                        'reference' => $reference,
                        'formatted_phone' => $formattedPhone,
                        'original_phone' => $phoneNumber,
                        'phone_length' => strlen($formattedPhone),
                        'amount' => $amountInKobo,
                        'response' => $responseData,
                    ]);

                    return [
                        'success' => false,
                        'message' => $errorMessage,
                        'reference' => $reference,
                    ];
                }
            }
            
            // If we reach here, payment was successful (either first attempt or retry)
            if (!$isSuccessful) {
                return [
                    'success' => false,
                    'message' => 'Payment initiation failed',
                    'reference' => $reference,
                ];
            }

            // Check if payment is already successful (common in test mode)
            $paymentStatus = Payment::STATUS_PENDING;
            $paymentData = $responseData['data'] ?? [];
            $chargeStatus = $paymentData['status'] ?? null;
            
            // In test mode, Paystack may return 'success' status immediately
            if ($chargeStatus === 'success' || $chargeStatus === 'successful') {
                $paymentStatus = Payment::STATUS_SUCCESS;
                Log::info('Payment immediately successful (test mode)', [
                    'order_id' => $order->id,
                    'reference' => $reference,
                    'status' => $chargeStatus,
                ]);
            }

            // Create payment record
            $payment = Payment::create([
                'order_id' => $order->id,
                'transaction_reference' => $reference,
                'amount' => $order->total_amount,
                'provider' => 'paystack',
                'method' => 'mpesa',
                'status' => $paymentStatus,
                'phone_number' => $phoneNumber,
                'raw_payload' => $responseData,
            ]);

            // If payment is immediately successful (test mode), update order status
            if ($paymentStatus === Payment::STATUS_SUCCESS) {
                DB::transaction(function () use ($order, $payment) {
                    $order->update([
                        'payment_status' => Order::PAYMENT_STATUS_PAID,
                        'status' => Order::STATUS_PREPARING, // Move to preparing when paid
                    ]);

                    Log::info('Order payment successful (immediate - test mode)', [
                        'order_id' => $order->id,
                        'payment_id' => $payment->id,
                        'reference' => $payment->transaction_reference,
                    ]);
                });
            }

            return [
                'success' => true,
                'message' => $paymentStatus === Payment::STATUS_SUCCESS 
                    ? 'Payment successful. Order is now being prepared.' 
                    : 'Payment initiated successfully. Please complete the payment on your phone.',
                'reference' => $reference,
                'data' => $responseData,
                'payment_status' => $paymentStatus,
            ];
        } catch (\Exception $e) {
            Log::error('Paystack Charge API exception', [
                'order_id' => $order->id,
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Payment initiation failed: ' . $e->getMessage(),
                'reference' => $reference,
            ];
        }
    }

    /**
     * Verify webhook signature using HMAC SHA512.
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $computedSignature = hash_hmac('sha512', $payload, $this->secretKey);

        return hash_equals($computedSignature, $signature);
    }

    /**
     * Generate a unique transaction reference.
     */
    private function generateTransactionReference(): string
    {
        return 'TXN_' . strtoupper(Str::random(12)) . '_' . time();
    }

    /**
     * Format phone number for Paystack M-Pesa (must be 254XXXXXXXXX format).
     * Paystack requires: 254 followed by exactly 9 digits (12 digits total, no +, no leading 0).
     * 
     * Examples:
     * - +254712345678 -> 254712345678
     * - 254712345678 -> 254712345678
     * - 0712345678 -> 254712345678
     * - 712345678 -> 254712345678
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Remove all non-digit characters
        $digitsOnly = preg_replace('/\D/', '', $phone);
        
        // If starts with 254, validate it's exactly 12 digits
        if (str_starts_with($digitsOnly, '254')) {
            if (strlen($digitsOnly) === 12) {
                return $digitsOnly; // Perfect format
            }
            // If longer than 12, take first 12
            if (strlen($digitsOnly) > 12) {
                return substr($digitsOnly, 0, 12);
            }
            // If shorter, extract the 9 digits after 254
            $after254 = substr($digitsOnly, 3);
            if (strlen($after254) >= 9) {
                return '254' . substr($after254, 0, 9);
            }
            // If we have at least some digits, pad with zeros (shouldn't happen but handle it)
            return '254' . str_pad($after254, 9, '0', STR_PAD_RIGHT);
        }
        
        // If starts with 0, remove 0 and prepend 254
        if (str_starts_with($digitsOnly, '0')) {
            $withoutZero = substr($digitsOnly, 1);
            // Should have 9 digits after 0
            if (strlen($withoutZero) >= 9) {
                return '254' . substr($withoutZero, 0, 9);
            }
            // If less than 9, pad with zeros (shouldn't happen)
            return '254' . str_pad($withoutZero, 9, '0', STR_PAD_RIGHT);
        }
        
        // If it's exactly 9 digits, prepend 254
        if (strlen($digitsOnly) === 9) {
            return '254' . $digitsOnly;
        }
        
        // If it's 10 digits, might be missing prefix or have extra digit
        if (strlen($digitsOnly) === 10) {
            // If starts with 7, it's likely 7XXXXXXXXX, prepend 254
            if (str_starts_with($digitsOnly, '7')) {
                return '254' . $digitsOnly;
            }
            // If starts with 0, we already handled it above, but just in case
            if (str_starts_with($digitsOnly, '0')) {
                return '254' . substr($digitsOnly, 1);
            }
        }
        
        // Last resort: extract last 9 digits and prepend 254
        if (strlen($digitsOnly) >= 9) {
            $lastNine = substr($digitsOnly, -9);
            return '254' . $lastNine;
        }
        
        // If we can't format it properly, log and return what we have
        Log::warning('Could not properly format phone number for Paystack', [
            'original' => $phone,
            'digits_only' => $digitsOnly,
            'length' => strlen($digitsOnly),
        ]);
        
        // Return a default format (will likely fail at Paystack but at least we tried)
        return strlen($digitsOnly) >= 9 ? '254' . substr($digitsOnly, -9) : '254' . str_pad($digitsOnly, 9, '0', STR_PAD_LEFT);
    }

    /**
     * Verify a transaction with Paystack.
     *
     * @return array<string, mixed>
     */
    public function verifyTransaction(string $reference): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->get("{$this->baseUrl}/transaction/verify/{$reference}");

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Paystack transaction verification failed', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => false,
                'message' => 'Transaction verification failed',
            ];
        }
    }
}
