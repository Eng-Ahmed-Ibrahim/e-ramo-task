<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\OrderStatus;
use App\Services\OrderService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;

class OrdersController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    public function updateStatus(UpdateOrderStatusRequest $request, int $id)
    {
        try {
            $order = $this->orderService->updateStatus($id,OrderStatus::from($request->validated('status')));

            return response()->json([
                'status' => true,
                'message' => 'Order status updated successfully.',
                'data' => $order,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error.',
                'errors' => $e->errors(),
            ], 422);
        }
    }
}
