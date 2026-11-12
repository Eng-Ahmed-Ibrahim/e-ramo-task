<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('branch_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('product_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('order_id')
                ->nullable()
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('reservation_id')
                ->nullable()
                ->constrained('stock_reservations')
                ->restrictOnDelete();

            $table->bigInteger('quantity_change');

            $table->unsignedInteger('quantity_before');
            $table->unsignedInteger('quantity_after');

            $table->string('movement_type');
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['branch_id', 'product_id', 'created_at']);
            $table->index(['order_id', 'movement_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
