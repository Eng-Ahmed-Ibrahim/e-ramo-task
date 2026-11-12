<?php
namespace App\Http\Controllers\Api\Admin;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;

class CategoriesController extends Controller
{
    public function index()
    {
        $categories = Category::latest()->paginate(15);

        return response()->json([
            'message' => 'Categories retrieved successfully.',
            'status' => true,
            'data' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:categories,slug'],
        ]);

        $category = Category::create($data);

        return response()->json([
            'message' => 'Category created successfully.',
            'status' => true,
            'data' => $category,
        ], 201);
    }

    public function show(int $id)
    {
        $category = Category::findOrFail($id);

        return response()->json([
            'message' => 'Category retrieved successfully.',
            'status' => true,
            'data' => $category,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $category = Category::findOrFail($id);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'slug')->ignore($category->id),
            ],
        ]);

        $category->update($data);

        return response()->json([
            'message' => 'Category updated successfully.',
            'status' => true,
            'data' => $category->fresh(),
        ]);
    }

    public function destroy(int $id)
    {
        $category = Category::findOrFail($id);

        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully.',
            'status' => true,
            'data' => null,
        ]);
    }
}
