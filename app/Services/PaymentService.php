<?php
namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function processPayment(
        int $orderId,
        string $idempotencyKey,
        string $outcome = 'success'
    ) {
    try{
         DB::transaction(function () use (
            $orderId,
            $idempotencyKey,
            $outcome
        ) {
            $existingPayment = Payment::where(
                'idempotency_key',
                $idempotencyKey
            )->lockForUpdate()->first();

            if ($existingPayment) {
                return $existingPayment;
            }
            $order = Order::lockForUpdate()->findOrFail($orderId);

            if ($order->status !== 'pending') {
                throw ValidationException::withMessages([
                    'order' => ['This order cannot be paid.'],
                ]);
            }
            $payment = Payment::create([
                'order_id' => $order->id,
                'amount' => $order->total,
                'payment_method' => 'simulated',
                'status' => $outcome === 'success'
                    ? 'succeeded'
                    : 'failed',
                'transaction_reference' => $outcome === 'success'
                    ? (string) Str::uuid()
                    : null,
                'idempotency_key' => $idempotencyKey,
                'paid_at' => $outcome === 'success'
                    ? now()
                    : null,
                'metadata' => [
                    'simulated_outcome' => $outcome,
                ],
            ]);
            if ($outcome === 'success') {
                $order->update([
                    'status' => 'paid',
                ]);
            }

            return $payment;
        });
    }
    catch (\Exception $e) {
        throw $e;
        
    }
}
}
