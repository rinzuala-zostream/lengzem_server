<?php

namespace App\Http\Controllers;

use App\Models\CoverImage;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CoverImageController extends Controller
{
    /**
     * List cover images (with optional pagination)
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);

        $covers = CoverImage::orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'status' => true,
            'data' => $covers
        ]);
    }

    /**
     * Store a new cover image
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'label' => 'required|string|max:255',
                'url' => 'required|url',
                'is_active' => 'nullable|boolean',
            ]);

            $cover = CoverImage::create([
                'label' => $validated['label'],
                'url' => $validated['url'],
                'is_active' => $request->get('is_active', true),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Cover image created successfully.',
                'data' => $cover
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Update a cover image
     */
    public function update(Request $request, $id)
    {
        try {
            $cover = CoverImage::findOrFail($id);

            $validated = $request->validate([
                'label' => 'sometimes|string|max:255',
                'url' => 'sometimes|url',
                'is_active' => 'sometimes|boolean',
            ]);

            $cover->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Cover image updated successfully.',
                'data' => $cover
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Delete a cover image
     */
    public function destroy($id)
    {
        $cover = CoverImage::findOrFail($id);
        $cover->delete();

        return response()->json([
            'status' => true,
            'message' => 'Cover image deleted successfully.'
        ]);
    }

    /**
     * Search cover images by label or slug
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:1'
        ]);

        $query = $request->get('q');

        $covers = CoverImage::where('label', 'LIKE', "%{$query}%")
            ->orWhere('slug', 'LIKE', "%{$query}%")
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $covers
        ]);
    }
}
