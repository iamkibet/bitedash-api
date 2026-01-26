<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\ProcessMpesaWebhook;
use App\Http\Controllers\Controller;
use App\Http\Requests\InitiatePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Services\MpesaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        private readonly MpesaService $mpesaService,
        private readonly ProcessMpesaWebhook $processWebhook
    ) {
    }

    /**
     * Initiate M-Pesa payment for an order.
     */
    public function initiate(InitiatePaymentRequest $request, Order $order): JsonResponse
    {
        // Verify order belongs to customer
        if ($order->customer_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized. This order does not belong to you.',
            ], 403);
        }

        // Verify order is unpaid
        if ($order->isPaid()) {
            return response()->json([
                'message' => 'Order is already paid.',
            ], 422);
        }

        // Verify order is not cancelled
        if ($order->status === Order::STATUS_CANCELLED) {
            return response()->json([
                'message' => 'Cannot initiate payment for a cancelled order.',
            ], 422);
        }

        try {
            $result = $this->mpesaService->initiateCharge(
                $order,
                $request->validated('phone_number'),
                $request->user()->email
            );

            if (!$result['success']) {
                return response()->json([
                    'message' => $result['message'],
                    'reference' => $result['reference'] ?? null,
                    'errors' => [
                        'phone_number' => [$result['message']],
                    ],
                ], 422);
            }
        } catch (\Exception $e) {
            Log::error('Payment initiation exception', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Payment initiation failed. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred during payment initiation.',
                'errors' => [
                    'phone_number' => ['Payment initiation failed. Please check your phone number format.'],
                ],
            ], 500);
        }

        $payment = \App\Models\Payment::where('transaction_reference', $result['reference'])->first();

        // Check if payment was immediately successful (test mode)
        $isImmediateSuccess = ($result['payment_status'] ?? null) === \App\Models\Payment::STATUS_SUCCESS;
        
        $message = $isImmediateSuccess
            ? 'Payment successful! Your order is now being prepared.'
            : 'Payment initiated successfully. Please complete the payment on your phone.';

        return response()->json([
            'message' => $message,
            'data' => new PaymentResource($payment->fresh(['order'])),
            'payment_instructions' => $isImmediateSuccess 
                ? 'Payment completed successfully' 
                : ($result['data']['data']['message'] ?? 'Check your phone for payment prompt'),
            'payment_status' => $payment->status,
        ], 201);
    }

    /**
     * Handle Paystack webhook.
     */
    public function webhook(Request $request): JsonResponse
    {
        $signature = $request->header('x-paystack-signature');

        if (!$signature) {
            Log::warning('Paystack webhook received without signature');
            return response()->json(['message' => 'Missing signature'], 400);
        }

        // Get raw payload for signature verification
        $payload = $request->getContent();
        
        // If content is empty (happens with postJson in tests), reconstruct from request data
        if (empty($payload)) {
            $payload = json_encode($request->all());
        }

        // Verify webhook signature
        if (!$this->mpesaService->verifyWebhookSignature($payload, $signature)) {
            Log::warning('Invalid Paystack webhook signature', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        // Get event data (use json() if available, otherwise all())
        $eventData = $request->json()?->all() ?? $request->all();

        try {
            // Process webhook asynchronously (in production, use queue)
            $this->processWebhook->handle($eventData);

            // Always return 200 OK immediately to prevent retries
            return response()->json(['message' => 'Webhook received'], 200);
        } catch (\Exception $e) {
            Log::error('Webhook processing error', [
                'error' => $e->getMessage(),
                'event' => $eventData['event'] ?? 'unknown',
            ]);

            // Still return 200 to prevent Paystack retries
            // Log the error for manual investigation
            return response()->json(['message' => 'Webhook received but processing failed'], 200);
        }
    }

    /**
     * Verify payment status.
     */
    public function verify(Request $request, string $reference): JsonResponse
    {
        $payment = \App\Models\Payment::where('transaction_reference', $reference)->first();

        if (!$payment) {
            return response()->json([
                'message' => 'Payment not found.',
            ], 404);
        }

        // Verify order belongs to customer
        if ($payment->order->customer_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        // Optionally verify with Paystack
        $verification = $this->mpesaService->verifyTransaction($reference);

        return response()->json([
            'data' => new PaymentResource($payment),
            'verification' => $verification,
        ]);
    }
}
