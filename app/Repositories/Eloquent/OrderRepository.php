<?php

namespace App\Repositories\Eloquent;

use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;

class OrderRepository implements OrderRepositoryInterface
{
    public function getAll()
    {
        return Order::with(['customer', 'branch', 'items.product'])
            ->latest()
            ->paginate(15);
    }

    public function find(int $id): Order
    {
        return Order::with([
            'customer',
            'branch',
            'items.product',
            'payments',
            'statusHistories',
        ])->findOrFail($id);
    }

    public function findByIdempotencyKey(string $key): ?Order
    {
        return Order::with(['items.product'])
            ->where('idempotency_key', $key)
            ->first();
    }

    public function create(array $data): Order
    {
        return Order::create($data);
    }

    public function createItems(Order $order, array $items): void
    {
        $order->items()->createMany($items);
    }

    public function createStatusHistory(
        Order $order,
        ?string $oldStatus,
        string $newStatus,
        ?string $notes = null
    ): void {
        $order->statusHistories()->create([
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'notes' => $notes,
        ]);
    }
}