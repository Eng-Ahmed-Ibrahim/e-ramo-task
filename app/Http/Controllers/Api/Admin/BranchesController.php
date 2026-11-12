<?php
namespace App\Http\Controllers\Api\Admin;

use App\Models\Branch;
use Illuminate\Http\Request;
use App\Services\BranchService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class BranchesController extends Controller
{
    public function __construct(
        protected BranchService $branchService
    ) {}

    public function index()
    {
        return response()->json([
            'status' => true,
            'data' => $this->branchService->getAll(),
        ]);
    }

    public function store(Request $request)
    {
        $data = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', 'unique:branches,code'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($data->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $data->errors(),
            ], 422);
        }

        $branch = $this->branchService->create($data->validated());

        return response()->json([
            'status' => true,
            'message' => 'Branch created successfully',
            'data' => $branch,
        ], 201);
    }

    public function show(Branch $branch)
    {
        return response()->json([
            'status' => true,
            'data' => $this->branchService->find($branch->id),
        ]);
    }

    public function update(Request $request, Branch $branch)
    {
        $data = Validator::make($request->all(), [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                'unique:branches,code,' . $branch->id,
            ],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($data->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $data->errors(),
            ], 422);
        }

        $branch = $this->branchService->update($branch, $data->validated());

        return response()->json([
            'status' => true,
            'message' => 'Branch updated successfully',
            'data' => $branch,
        ]);
    }

    public function destroy(Branch $branch)
    {
        $this->branchService->delete($branch);

        return response()->json([
            'status' => true,
            'message' => 'Branch deleted successfully',
        ]);
    }
}
