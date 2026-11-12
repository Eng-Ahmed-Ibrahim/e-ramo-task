<?php

namespace App\Services;

use App\Models\Product;

class ProductService
{
    public function getAll(array $filters = [])
    {
        return Product::with('category')
            ->when(isset($filters['category_id']), function ($query) use ($filters) {
                $query->where('category_id', $filters['category_id']);
            })
            ->when(isset($filters['is_active']), function ($query) use ($filters) {
                $query->where('is_active', $filters['is_active']);
            })
            ->latest()
            ->paginate(15);
    }

    public function find(int $id): Product
    {
        return Product::with('category')->findOrFail($id);
    }

    public function create(array $data): Product
    {
        $product = Product::create($data);

        return $product->load('category');
    }

    public function update(int $id, array $data): Product
    {
        $product = $this->find($id);

        $product->update($data);

        return $product->fresh('category');
    }

    public function delete(int $id): bool
    {
        $product = $this->find($id);

        return $product->delete();
    }
}
