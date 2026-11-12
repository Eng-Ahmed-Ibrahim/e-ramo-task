<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\ProductService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ProductsController extends Controller
{
    public function __construct(
        private ProductService $productService
    ) {}

    public function index(Request $request)
    {
        $products = $this->productService->getAll(
            $request->only(['category_id', 'is_active'])
        );

        return response()->json([
            'message' => 'Products retrieved successfully.',
            'status' => true,
            'data' => $products,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $baseSku = Str::upper(Str::slug($data['name']));
        $sku = $baseSku;
        $counter = 1;

        while (Product::where('sku', $sku)->exists()) {
            $sku = $baseSku . '-' . $counter++;
        }

        $data['sku'] = $sku;
        $product = $this->productService->create($data);

        return response()->json([
            'message' => 'Product created successfully.',
            'status' => true,
            'data' => $product,
        ], 201);
    }

    public function show(int $id)
    {
        $product = $this->productService->find($id);

        return response()->json([
            'message' => 'Product retrieved successfully.',
            'status' => true,
            'data' => $product,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $product = Product::findOrFail($id);

        $validator = Validator::make($request->all(),   [
            'category_id' => ['sometimes', 'required', 'exists:categories,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $baseSku = Str::upper(Str::slug($data['name']));
        $sku = $baseSku;
        $counter = 1;

        while (Product::where('sku', $sku)->exists()) {
            $sku = $baseSku . '-' . $counter++;
        }

        $data['sku'] = $sku;

        $product = $this->productService->update($id, $data);

        return response()->json([
            'message' => 'Product updated successfully.',
            'status' => true,
            'data' => $product,
        ]);
    }

    public function destroy(int $id)
    {
        $this->productService->delete($id);

        return response()->json([
            'message' => 'Product deleted successfully.',
            'status' => true,
            'data' => null,
        ]);
    }
}
