<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\WarehouseService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class WarehousesController extends Controller
{
    public function __construct(
        private WarehouseService $warehouseService
    ) {}

    public function index(Request $request)
    {
        $warehouses = $this->warehouseService->getAll(
            $request->only(['branch_id', 'is_active'])
        );

        return response()->json([
            'message' => 'Warehouses retrieved successfully.',
            'status' => true,
            'data' => $warehouses,
        ]);
    }

    public function store(Request $request)
    {
        $validators = Validator::make($request->all(), [
            'branch_id' => ['required', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('warehouses', 'code')
                    ->where('branch_id', $request->branch_id),
            ],
            'address' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($validators->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'status' => false,
                'errors' => $validators->errors(),
            ], 422);
        }

        $data = $validators->validated();
        try {
            $warehouse = $this->warehouseService->create($data);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error.',
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json([
            'message' => 'Warehouse created successfully.',
            'status' => true,
            'data' => $warehouse,
        ], 201);
    }

    public function show(int $id)
    {
        $warehouse = $this->warehouseService->find($id);

        return response()->json([
            'message' => 'Warehouse retrieved successfully.',
            'status' => true,
            'data' => $warehouse,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $warehouse = Warehouse::findOrFail($id);

        $validators = Validator::make($request->all(), [
            'branch_id' => ['sometimes', 'required', 'exists:branches,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('warehouses', 'code')
                    ->where('branch_id', $request->input('branch_id', $warehouse->branch_id))
                    ->ignore($warehouse->id),
            ],
            'address' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        if ($validators->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'status' => false,
                'errors' => $validators->errors(),
            ], 422);
        }
        $data = $validators->validated();
        try {
            $warehouse = $this->warehouseService->update($id, $data);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error.',
                'errors' => $e->errors(),
            ], 422);
        }


        return response()->json([
            'message' => 'Warehouse updated successfully.',
            'status' => true,
            'data' => $warehouse,
        ]);
    }

    public function destroy(int $id)
    {
        $this->warehouseService->delete($id);

        return response()->json([
            'message' => 'Warehouse deleted successfully.',
            'status' => true,
            'data' => null,
        ]);
    }
}
