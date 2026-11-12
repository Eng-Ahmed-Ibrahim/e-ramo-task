<?php

namespace App\Http\Controllers\Api\Customer;

use App\Models\Order;
use App\Enums\OrderStatus;
use Illuminate\Http\Request;
use App\Services\OrderService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use App\Http\Resources\OrderResource;
use App\Http\Requests\StoreOrderRequest;

class OrdersController extends Controller
{

    public function __construct(
        protected OrderService $orderService
    ) {}


    public function create(StoreOrderRequest $request)
    {
        try {
            $data = $request->validated();
            $data['customer_id'] = $request->user()->id;

            $order = $this->orderService->create($data);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error.',
                'errors' => $e->errors(),
            ], 422);
        }


        return response()->json([
            'status' => true,
            'message' => 'Order created successfully',
            'data' => $order,
        ], 201);
    }

    public function index(Request $request)
    {
        return OrderResource::collection(
            $this->orderService->getForCustomer($request->user()->id)
        );
    }

    public function show(Order $order)
    {
        Gate::authorize('view', $order);
        return new OrderResource($order->load(['items']));
    }
}
