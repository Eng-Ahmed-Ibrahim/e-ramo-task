<?php

namespace App\Http\Controllers\Customer;

use Illuminate\Http\Request;
use App\Services\PaymentService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    public function pay(Request $request, int $orderId)
    {
        $validator = Validator::make($request->all(), [
            'idempotency_key' => ['required', 'string', 'max:255'],
            'outcome' => ['required', 'in:success,failure'],
        ]);
        $data = $validator->validate();

        try {

            $payment = $this->paymentService->processPayment(
                $orderId,
                $data['idempotency_key'],
                $data['outcome']
            );

            return response()->json([
                'message' => 'Payment processed successfully.',
                'status' => true,
                'data' => $payment,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Payment processing failed.',
                'status' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
