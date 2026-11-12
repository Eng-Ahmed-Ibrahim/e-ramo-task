<?php

namespace App\Models;

use App\Models\Order;
use App\Models\Branch;
use App\Models\Product;
use App\Models\StockReservation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    protected $fillable = [
        'branch_id',
        'product_id',
        'order_id',
        'reservation_id',
        'quantity_change',
        'quantity_before',
        'quantity_after',
        'movement_type',
        'notes',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(StockReservation::class);
    }
}