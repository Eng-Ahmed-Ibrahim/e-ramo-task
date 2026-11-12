<?php
namespace App\Repositories\Contracts;

use App\Models\Order;

interface OrderRepositoryInterface
{
    public function getAll();

    public function find(int $id): Order;

    public function findByIdempotencyKey(string $key): ?Order;

    public function create(array $data): Order;

    public function createItems(Order $order, array $items): void;

    public function createStatusHistory(
        Order $order,
        ?string $oldStatus,
        string $newStatus,
        ?string $notes = null
    ): void;
}