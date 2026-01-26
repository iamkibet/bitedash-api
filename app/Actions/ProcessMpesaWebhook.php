<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Order;
use App\Models\Payment;
use App\Services\MpesaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessMpesaWebhook
{
    public function __construct(
        private readonly MpesaService $mpesaService
    ) {
    }

    /**
     * Process Paystack webhook event.
     *
     * @param array<string, mixed> $eventData
     */
    public function handle(array $eventData): void
    {
        $event = $eventData['event'] ?? null;
        $data = $eventData['data'] ?? [];

        if (!$event || !isset($data['reference'])) {
            Log::warning('Invalid webhook payload', ['event_data' => $eventData]);
            return;
        }

        $reference = $data['reference'];
        $payment = Payment::where('transaction_reference', $reference)->first();

        if (!$payment) {
            Log::warning('Payment not found for webhook', ['reference' => $reference]);
            return;
        }

        // Update payment record with latest webhook data
        $payment->update([
            'raw_payload' => $eventData,
        ]);

        // Process based on event type
        match ($event) {
            'charge.success' => $this->handleChargeSuccess($payment, $data),
            'charge.failed' => $this->handleChargeFailed($payment, $data),
            'transfer.failed' => $this->handleTransferFailed($payment, $data),
            default => Log::info('Unhandled webhook event', [
                'event' => $event,
                'reference' => $reference,
            ]),
        };
    }

    /**
     * Handle successful charge event.
     * This is called when Paystack sends a charge.success webhook.
     * In test mode, payments may be immediately successful or come via webhook.
     *
     * @param array<string, mixed> $data
     */
    private function handleChargeSuccess(Payment $payment, array $data): void
    {
        DB::transaction(function () use ($payment, $data) {
            // Update payment status to success
            $payment->update([
                'status' => Payment::STATUS_SUCCESS,
                'raw_payload' => array_merge($payment->raw_payload ?? [], $data),
            ]);

            // Update order status and payment status
            $order = $payment->order;
            if ($order) {
                // Only update if order is not already paid (idempotent)
                if ($order->payment_status !== Order::PAYMENT_STATUS_PAID) {
                    $order->update([
                        'payment_status' => Order::PAYMENT_STATUS_PAID,
                        'status' => Order::STATUS_PREPARING, // Move to preparing when paid
                    ]);

                    Log::info('Order payment successful via webhook', [
                        'order_id' => $order->id,
                        'payment_id' => $payment->id,
                        'reference' => $payment->transaction_reference,
                        'previous_status' => $order->getOriginal('status'),
                    ]);
                } else {
                    Log::info('Order already paid, skipping update', [
                        'order_id' => $order->id,
                        'payment_id' => $payment->id,
                        'reference' => $payment->transaction_reference,
                    ]);
                }
            }
        });
    }

    /**
     * Handle failed charge event.
     *
     * @param array<string, mixed> $data
     */
    private function handleChargeFailed(Payment $payment, array $data): void
    {
        DB::transaction(function () use ($payment, $data) {
            // Update payment status
            $payment->update([
                'status' => Payment::STATUS_FAILED,
                'failure_reason' => $data['gateway_response'] ?? $data['message'] ?? 'Payment failed',
                'raw_payload' => array_merge($payment->raw_payload ?? [], $data),
            ]);

            // Update order payment status
            $order = $payment->order;
            if ($order && $order->payment_status === Order::PAYMENT_STATUS_UNPAID) {
                $order->update([
                    'payment_status' => Order::PAYMENT_STATUS_FAILED,
                ]);

                Log::warning('Order payment failed', [
                    'order_id' => $order->id,
                    'payment_id' => $payment->id,
                    'reference' => $payment->transaction_reference,
                    'reason' => $payment->failure_reason,
                ]);
            }
        });
    }

    /**
     * Handle failed transfer event (refund scenario).
     *
     * @param array<string, mixed> $data
     */
    private function handleTransferFailed(Payment $payment, array $data): void
    {
        DB::transaction(function () use ($payment, $data) {
            // Update payment status
            $payment->update([
                'status' => Payment::STATUS_FAILED,
                'failure_reason' => $data['message'] ?? 'Transfer failed',
                'raw_payload' => array_merge($payment->raw_payload ?? [], $data),
            ]);

            // Update order payment status
            $order = $payment->order;
            if ($order) {
                $order->update([
                    'payment_status' => Order::PAYMENT_STATUS_FAILED,
                ]);

                Log::warning('Order transfer failed', [
                    'order_id' => $order->id,
                    'payment_id' => $payment->id,
                    'reference' => $payment->transaction_reference,
                ]);
            }
        });
    }
}
