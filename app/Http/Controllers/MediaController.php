<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function index($articleId)
    {
        try {
            $mediaItems = Media::where('article_id', $articleId)->get();

            return response()->json([
                'status' => true,
                'message' => 'Media items retrieved successfully.',
                'data' => $mediaItems,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve media items.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request, $articleId)
    {
        try {
            $data = $request->validate([
                'media_type' => 'required|in:image,video',
                'media_url' => 'required|url',
                'caption' => 'nullable|string',
                'order' => 'nullable|integer',
            ]);

            $data['article_id'] = $articleId;

            $media = Media::create($data);

            return response()->json([
                'status' => true,
                'message' => 'Media created successfully.',
                'data' => $media,
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
                'message' => 'Failed to create media.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $media = Media::findOrFail($id);

            $data = $request->validate([
                'media_type' => 'sometimes|in:image,video',
                'media_url' => 'sometimes|url',
                'caption' => 'nullable|string',
                'order' => 'nullable|integer',
            ]);

            $media->update($data);

            return response()->json([
                'status' => true,
                'message' => 'Media updated successfully.',
                'data' => $media,
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
                'message' => 'Failed to update media.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $media = Media::findOrFail($id);
            $media->delete();

            return response()->json([
                'status' => true,
                'message' => 'Media deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete media.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
