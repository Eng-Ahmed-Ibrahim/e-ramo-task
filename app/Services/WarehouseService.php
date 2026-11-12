<?php
namespace App\Services;

use App\Models\Warehouse;

class WarehouseService
{
    public function getAll(array $filters = [])
    {
        return Warehouse::query()
            ->with('branch')
            ->when(isset($filters['branch_id']), function ($query) use ($filters) {
                $query->where('branch_id', $filters['branch_id']);
            })
            ->when(isset($filters['is_active']), function ($query) use ($filters) {
                $query->where('is_active', $filters['is_active']);
            })
            ->latest()
            ->paginate(15);
    }

    public function find(int $id): Warehouse
    {
        return Warehouse::with('branch')->findOrFail($id);
    }

    public function create(array $data): Warehouse
    {
        return Warehouse::create($data);
    }

    public function update(int $id, array $data): Warehouse
    {
        $warehouse = $this->find($id);

        $warehouse->update($data);

        return $warehouse->fresh('branch');
    }

    public function delete(int $id): bool
    {
        $warehouse = $this->find($id);

        return $warehouse->delete();
    }
}
