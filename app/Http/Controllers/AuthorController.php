<?php

namespace App\Http\Controllers;

use App\Models\Author;
use Illuminate\Http\Request;

class AuthorController extends Controller
{

    // List all authors
    public function index(Request $request)
    {
        try {
            $query = Author::with('user')
                ->withCount('articles'); // Count articles for each author

            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            return response()->json([
                'status' => true,
                'message' => 'Authors retrieved successfully.',
                'data' => $query->paginate(10)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve authors.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Create author
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'user_id' => 'required|exists:users,id',
                'pen_name' => 'nullable|string|max:100',
                'bio' => 'nullable|string',
                'social_links' => 'nullable|json',
            ]);

            $author = Author::create($data);

            return response()->json([
                'status' => true,
                'message' => 'Author created successfully.',
                'data' => $author
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
                'message' => 'Failed to create author.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Update author
    public function update(Request $request, $id)
    {
        try {
            $author = Author::findOrFail($id);

            $data = $request->validate([
                'pen_name' => 'sometimes|string|max:100',
                'bio' => 'nullable|string',
                'social_links' => 'nullable|json',
            ]);

            $author->update($data);

            return response()->json([
                'status' => true,
                'message' => 'Author updated successfully.',
                'data' => $author
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
                'message' => 'Failed to update author.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Delete author
    public function destroy($id)
    {
        try {
            $author = Author::findOrFail($id);
            $author->delete();

            return response()->json([
                'status' => true,
                'message' => 'Author deleted successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete author.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
