<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TagController extends Controller
{
    public function index()
    {
        try {
            $tags = Tag::orderBy('name', 'asc')->paginate(20);  // Sort by 'name' in ascending order
            return response()->json([
                'status' => true,
                'message' => 'Tags retrieved successfully.',
                'data' => $tags,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve tags.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function search(Request $request)
    {
        \Log::info('Search function called');
        \Log::info('Search term: ' . $request->query('name'));

        try {
            $searchTerm = $request->query('name'); // Get the search term from the query string

            if (!$searchTerm) {
                return response()->json([
                    'status' => false,
                    'message' => 'No search term provided.',
                ], 400);
            }

            $tags = Tag::where('name', 'like', '%' . $searchTerm . '%') // search by name
                ->orderBy('name', 'asc')
                ->paginate(20); // Sort by 'name' in ascending order

            if ($tags->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No tags found matching the search term.',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Tags found successfully.',
                'data' => $tags,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to search tags.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $tag = Tag::findOrFail($id);
            return response()->json([
                'status' => true,
                'message' => 'Tag retrieved successfully.',
                'data' => $tag,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve tag.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required|string|max:100',
            ]);

            $data['slug'] = Str::slug($data['name']);

            $tag = Tag::create($data);

            return response()->json([
                'status' => true,
                'message' => 'Tag created successfully.',
                'data' => $tag,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create tag.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $tag = Tag::findOrFail($id);

            $data = $request->validate([
                'name' => 'sometimes|string|max:100',
            ]);

            if (isset($data['name'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $tag->update($data);

            return response()->json([
                'status' => true,
                'message' => 'Tag updated successfully.',
                'data' => $tag,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update tag.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $tag = Tag::findOrFail($id);
            $tag->delete();

            return response()->json([
                'status' => true,
                'message' => 'Tag deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete tag.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
