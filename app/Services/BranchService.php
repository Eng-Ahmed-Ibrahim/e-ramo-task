<?php
namespace App\Services;

use App\Models\Branch;

class BranchService
{
    public function getAll(int $perPage = 15)
    {
        return Branch::latest()->paginate($perPage);
    }

    public function find(int $id): Branch
    {
        return Branch::findOrFail($id);
    }

    public function create(array $data): Branch
    {
        return Branch::create($data);
    }

    public function update(Branch $branch, array $data): Branch
    {
        $branch->update($data);

        return $branch->refresh();
    }

    public function delete(Branch $branch): void
    {
        $branch->delete();
    }
}
