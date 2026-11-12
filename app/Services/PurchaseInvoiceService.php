<?php
namespace App\Services;

use App\Models\Inventory;
use Illuminate\Support\Arr;
use App\Models\PurchaseInvoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseInvoiceService
{
    public function create(array $data): PurchaseInvoice
    {
        return DB::transaction(function () use ($data) {

            $items = $this->prepareItems($data['items']);

            $totals = $this->calculateTotals(
                $items,
                $data['discount'] ?? 0,
                $data['tax'] ?? 0
            );

            $invoiceData = Arr::except($data, [
                'items',
                'subtotal',
                'total',
                'status',
            ]);

            $invoice = PurchaseInvoice::create([
                ...$invoiceData,
                ...$totals,
                'status' => 'pending',
            ]);

            $invoice->items()->createMany($items);

            return $this->loadInvoice($invoice->id);
        }, 3);
    }

    public function approve(int $id): PurchaseInvoice
    {
        return DB::transaction(function () use ($id) {

            $invoice = PurchaseInvoice::query()
                ->lockForUpdate()
                ->findOrFail($id);

            $this->ensurePending($invoice);

            $invoice->load('items');

            $items = $invoice->items
                ->sortBy('product_id')
                ->values();

            $productIds = $items->pluck('product_id')
                ->unique()
                ->values();

            $this->initializeInventories(
                $invoice->warehouse_id,
                $productIds
            );

            $inventories = $this->lockInventories(
                $invoice->warehouse_id,
                $productIds
            );

            foreach ($items as $item) {
                $inventory = $inventories->get($item->product_id);

                if (!$inventory) {
                    throw new \RuntimeException(
                        "Inventory not found for product {$item->product_id}."
                    );
                }

                $inventory->increment(
                    'quantity',
                    $item->quantity
                );
            }

            $invoice->update([
                'status' => 'approved',
            ]);

            return $this->loadInvoice($invoice->id);
        }, 3);
    }

    public function cancel(int $id): PurchaseInvoice
    {
        return DB::transaction(function () use ($id) {

            $invoice = PurchaseInvoice::query()
                ->lockForUpdate()
                ->findOrFail($id);

            $this->ensurePending($invoice);

            $invoice->update([
                'status' => 'cancelled',
            ]);

            return $this->loadInvoice($invoice->id);
        }, 3);
    }

    public function getAll(array $filters = [])
    {
        return PurchaseInvoice::query()
            ->with([
                'branch',
                'warehouse',
                'items.product',
            ])
            ->when(
                !empty($filters['branch_id']),
                fn($query) => 
                $query->where('branch_id',$filters['branch_id'])
            )
            ->when(
                !empty($filters['warehouse_id']),
                fn($query) => 
                $query->where('warehouse_id',$filters['warehouse_id'])
            )
            ->when(
                !empty($filters['status']),
                fn($query) => $query->where(
                    'status',
                    $filters['status']
                )
            )
            ->latest()
            ->paginate(15);
    }

    public function find(int $id): PurchaseInvoice
    {
        return $this->loadInvoice($id);
    }

    private function prepareItems(array $items): array
    {
        $processedItems = [];

        foreach ($items as $item) {

            $quantity = (int) $item['quantity'];
            $unitCost = (float) $item['unit_cost'];

            $grossTotal = $this->toCents(
                $quantity * $unitCost
            );

            $discount = $this->toCents(
                $item['discount'] ?? 0
            );

            if ($discount > $grossTotal) {
                throw ValidationException::withMessages([
                    'items' => [
                        'Item discount cannot exceed its total.',
                    ],
                ]);
            }

            $processedItems[] = [
                'product_id' => $item['product_id'],
                'quantity' => $quantity,
                'unit_cost' => $this->fromCents(
                    $this->toCents($unitCost)
                ),
                'discount' => $this->fromCents($discount),
                'total' => $this->fromCents(
                    $grossTotal - $discount
                ),
            ];
        }

        return $processedItems;
    }

    private function calculateTotals(
        array $items,
        float|int $invoiceDiscount,
        float|int $tax
    ): array {

        $subtotal = 0;
        $itemsDiscount = 0;

        foreach ($items as $item) {
            $subtotal += $this->toCents(
                $item['quantity'] * (float) $item['unit_cost']
            );

            $itemsDiscount += $this->toCents(
                $item['discount']
            );
        }

        $discountableTotal = $subtotal - $itemsDiscount;

        $invoiceDiscount = $this->toCents($invoiceDiscount);
        $tax = $this->toCents($tax);

        if ($invoiceDiscount > $discountableTotal) {
            throw ValidationException::withMessages([
                'discount' => [
                    'Invoice discount cannot exceed the items total.',
                ],
            ]);
        }

        $total = $discountableTotal
            - $invoiceDiscount
            + $tax;

        return [
            'subtotal' => $this->fromCents($subtotal),
            'discount' => $this->fromCents($invoiceDiscount),
            'tax' => $this->fromCents($tax),
            'total' => $this->fromCents($total),
        ];
    }

    private function initializeInventories(
        int $warehouseId,
        $productIds
    ): void {

        $now = now();

        $records = $productIds->map(fn($productId) => [
            'warehouse_id' => $warehouseId,
            'product_id' => $productId,
            'quantity' => 0,
            'reserved_quantity' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        DB::table('inventories')->insertOrIgnore($records);
    }

    private function lockInventories(
        int $warehouseId,
        $productIds
    ) {
        return Inventory::query()
            ->where('warehouse_id', $warehouseId)
            ->whereIn('product_id', $productIds)
            ->orderBy('product_id')
            ->lockForUpdate()
            ->get()
            ->keyBy('product_id');
    }

    private function ensurePending(
        PurchaseInvoice $invoice
    ): void {

        if ($invoice->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => [
                    'Only pending invoices can be processed.',
                ],
            ]);
        }
    }

    private function loadInvoice(int $id): PurchaseInvoice
    {
        return PurchaseInvoice::query()
            ->with([
                'branch',
                'warehouse',
                'items.product',
            ])
            ->findOrFail($id);
    }

    private function toCents(float|int|string $amount): int
    {
        return (int) round((float) $amount * 100);
    }

    private function fromCents(int $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
