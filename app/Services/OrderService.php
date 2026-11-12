<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Inventory;
use App\Enums\OrderStatus;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Database\UniqueConstraintViolationException;

class OrderService
{
    public function __construct(
        protected OrderRepositoryInterface $orderRepository
    ) {}

    public function getAll()
    {
        return $this->orderRepository->getAll();
    }

    public function find(int $id): Order
    {
        return $this->orderRepository->find($id);
    }

    public function create(array $data)
    {
        if ($existing = $this->orderRepository->findByIdempotencyKey($data['idempotency_key'])) {
            throw ValidationException::withMessages([
                'idempotency_key' => ['this order already exists.'],
            ]);
        }

        try {
             DB::transaction(function () use ($data) {

                $branch = Branch::with('warehouse')->findOrFail($data['branch_id']);

                if (!$branch->warehouse) {
                    throw ValidationException::withMessages([
                        'branch_id' => ['This branch has no warehouse.'],
                    ]);
                }

                // Merge duplicate product lines, sorted by product_id (consistent lock order).
                $items = collect($data['items'])
                    ->groupBy('product_id')
                    ->map(fn(Collection $lines, $productId) => [
                        'product_id' => (int) $productId,
                        'quantity'   => (int) $lines->sum('quantity'),
                    ])
                    ->sortKeys()
                    ->values();

                foreach ($items as $item) {
                    if ($item['quantity'] <= 0) {
                        throw ValidationException::withMessages([
                            'items' => ['Quantity must be greater than zero.'],
                        ]);
                    }
                }

                $productIds = $items->pluck('product_id');

                $products = Product::whereIn('id', $productIds)
                    ->get()
                    ->keyBy('id');

                if ($products->count() !== $productIds->count()) {
                    throw ValidationException::withMessages([
                        'items' => ['One or more products do not exist.'],
                    ]);
                }

                $inventories = $this->lockInventories($branch->warehouse->id, $productIds);

                if ($inventories->count() !== $productIds->count()) {
                    throw ValidationException::withMessages([
                        'items' => ['Inventory not found for one or more products in this branch.'],
                    ]);
                }

                $orderItems = [];
                $subtotal = 0;

                foreach ($items as $item) {
                    $product = $products->get($item['product_id']);
                    $inventory = $inventories->get($product->id);
                    $quantity = $item['quantity'];

                    $availableQuantity = $inventory->quantity - $inventory->reserved_quantity;

                    if ($quantity > $availableQuantity) {
                        throw ValidationException::withMessages([
                            'items' => [
                                "Insufficient stock for product: {$product->name}. Available: {$availableQuantity}.",
                            ],
                        ]);
                    }

                    $unitPrice = $product->price;
                    $total = round($unitPrice * $quantity, 2);
                    $subtotal += $total;

                    $orderItems[] = [
                        'product_id' => $product->id,
                        'unit_price' => $unitPrice,
                        'quantity'   => $quantity,
                        'total'      => $total,
                    ];
                }

                $subtotal = round($subtotal, 2);

                $order = $this->orderRepository->create([
                    'order_number'    => 'ORD-' . $branch->code . '-' . Str::upper(Str::random(6)),
                    'customer_id'     => $data['customer_id'],
                    'branch_id'       => $branch->id,
                    'status'          => OrderStatus::PENDING,
                    'subtotal'        => $subtotal,
                    'total'           => $subtotal,
                    'idempotency_key' => $data['idempotency_key'],
                ]);

                $this->orderRepository->createItems($order, $orderItems);

                foreach ($orderItems as $item) {
                    $inventory = $inventories->get($item['product_id']);

                    $inventory->increment('reserved_quantity', $item['quantity']);

                    $this->recordStockMovement(
                        inventory: $inventory,
                        order: $order,
                        type: 'reservation',
                        quantityBefore: $inventory->quantity,
                        quantityChange: 0, // on-hand unchanged, only reserved
                        notes: "Reserved {$item['quantity']} units for order {$order->order_number}"
                    );
                }

                $this->orderRepository->createStatusHistory(
                    $order,
                    null,
                    OrderStatus::PENDING->value,
                    'Order created and stock reserved'
                );

                return $this->orderRepository->find($order->id);
            }, 3);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } 
    }

    public function updateStatus(int $orderId, OrderStatus $newStatus): Order
    {
        return DB::transaction(function () use ($orderId, $newStatus) {

            $order = Order::with(['items', 'branch.warehouse'])
                ->lockForUpdate()
                ->findOrFail($orderId);

            $currentStatus = $order->status instanceof OrderStatus
                ? $order->status
                : OrderStatus::from($order->status);

            if (!$currentStatus->canTransitionTo($newStatus)) {
                throw ValidationException::withMessages([
                    'status' => ['Cannot transition from ' . $currentStatus->value . ' to ' . $newStatus->value],
                ]);
            }

            if ($newStatus === OrderStatus::COMPLETED) {
                $this->completeStock($order);
            }

            if ($newStatus === OrderStatus::CANCELLED) {
                if ($currentStatus === OrderStatus::COMPLETED) {
                    throw ValidationException::withMessages([
                        'status' => ['Completed orders cannot be cancelled.'],
                    ]);
                }

                $this->releaseStock($order);
            }

            $order->status = $newStatus;
            $order->save();

            $this->orderRepository->createStatusHistory(
                $order,
                $currentStatus->value,
                $newStatus->value,
                "Order status changed from {$currentStatus->value} to {$newStatus->value}"
            );

            return $order->fresh(['items']);
        }, 3);
    }

    /**
     * Reserved -> sold: decrease on-hand and reserved quantities.
     */
    private function completeStock(Order $order): void
    {
        $items = $order->items->sortBy('product_id');
        $inventories = $this->lockInventories(
            $this->warehouseIdFor($order),
            $items->pluck('product_id')
        );

        foreach ($items as $item) {
            $inventory = $inventories->get($item->product_id);

            if (
                !$inventory ||
                $inventory->reserved_quantity < $item->quantity ||
                $inventory->quantity < $item->quantity
            ) {
                throw ValidationException::withMessages([
                    'stock' => ["Insufficient stock or reservation for product {$item->product_id}."],
                ]);
            }

            $before = $inventory->quantity;

            $inventory->decrement('quantity', $item->quantity);
            $inventory->decrement('reserved_quantity', $item->quantity);

            $this->recordStockMovement(
                inventory: $inventory,
                order: $order,
                type: 'sale',
                quantityBefore: $before,
                quantityChange: -$item->quantity,
                notes: "Sold {$item->quantity} units for order {$order->order_number}"
            );
        }
    }

    /**
     * Cancel: give the reserved stock back (on-hand unchanged).
     */
    private function releaseStock(Order $order): void
    {
        $items = $order->items->sortBy('product_id');
        $inventories = $this->lockInventories(
            $this->warehouseIdFor($order),
            $items->pluck('product_id')
        );

        foreach ($items as $item) {
            $inventory = $inventories->get($item->product_id);

            if (!$inventory || $inventory->reserved_quantity < $item->quantity) {
                throw ValidationException::withMessages([
                    'stock' => ["Reservation not found for product {$item->product_id}."],
                ]);
            }

            $inventory->decrement('reserved_quantity', $item->quantity);

            $this->recordStockMovement(
                inventory: $inventory,
                order: $order,
                type: 'release',
                quantityBefore: $inventory->quantity,
                quantityChange: 0,
                notes: "Released {$item->quantity} units for order {$order->order_number}"
            );
        }
    }

    private function warehouseIdFor(Order $order): int
    {
        $warehouse = $order->branch?->warehouse;

        if (!$warehouse) {
            throw ValidationException::withMessages([
                'branch_id' => ['This branch has no warehouse.'],
            ]);
        }

        return $warehouse->id;
    }

    /**
     * Lock the warehouse's inventory rows in a consistent order (by id) to avoid deadlocks.
     */
    private function lockInventories(int $warehouseId, Collection $productIds): Collection
    {
        return Inventory::query()
            ->where('warehouse_id', $warehouseId)
            ->whereIn('product_id', $productIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('product_id');
    }

    private function recordStockMovement(
        Inventory $inventory,
        Order $order,
        string $type,
        int $quantityBefore,
        int $quantityChange,
        string $notes
    ): void {
        DB::table('inventory_movements')->insert([
            'branch_id'       => $order->branch_id,
            'product_id'      => $inventory->product_id,
            'order_id'        => $order->id,
            'reservation_id'  => null,
            'quantity_change' => $quantityChange,
            'quantity_before' => $quantityBefore,
            'quantity_after'  => $quantityBefore + $quantityChange,
            'movement_type'   => $type,
            'notes'           => $notes,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
    }
    public function getForCustomer(int $customerId)
    {
        return Order::where('customer_id', $customerId)
            ->with('items')
            ->latest()
            ->paginate(15);
    }
}
