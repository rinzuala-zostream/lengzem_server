<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        try {
            $categories = Category::withCount('articles')->paginate(15);
            return response()->json([
                'status' => true,
                'message' => 'Categories retrieved successfully',
                'data' => $categories
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $category = Category::withCount('articles')->findOrFail($id);
            return response()->json([
                'status' => true,
                'message' => 'Category retrieved successfully.',
                'data' => $category
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve category.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required|string|max:100',
                'description' => 'nullable|string',
            ]);

            $data['slug'] = Str::slug($data['name']);

            $category = Category::create($data);

            return response()->json([
                'status' => true,
                'message' => 'Category created successfully.',
                'data' => $category
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create category.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $category = Category::findOrFail($id);

            $data = $request->validate([
                'name' => 'sometimes|string|max:100',
                'description' => 'nullable|string',
            ]);

            if (isset($data['name'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $category->update($data);

            return response()->json([
                'status' => true,
                'message' => 'Category updated successfully.',
                'data' => $category
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update category.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $category = Category::findOrFail($id);
            $category->delete();

            return response()->json([
                'status' => true,
                'message' => 'Category deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete category.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
