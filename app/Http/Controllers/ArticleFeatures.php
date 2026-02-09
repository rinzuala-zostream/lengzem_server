<?php

namespace App\Http\Controllers;

use App\Models\ArticleFeatureModel;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class ArticleFeatures extends Controller
{
    /**
     * Display a listing of the article features.
     */
    public function index()
    {
        try {
            $features = ArticleFeatureModel::orderBy('month_year', 'desc')->get();

            return response()->json([
                'success' => true,
                'message' => 'Article features fetched successfully.',
                'data' => $features
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch article features.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created article feature in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'month_year' => 'nullable|string|max:55',
                'title' => 'required|string|max:255',
                'description' => 'nullable|string|max:255',
                'img_url' => 'nullable|string',
            ]);

            $feature = ArticleFeatureModel::create([
                'month_year' => $request->month_year,
                'title' => $request->title,
                'description' => $request->description,
                'img_url' => $request->img_url,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Article feature created successfully!',
                'data' => $feature
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create article feature.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified article feature.
     */
    public function show($id)
    {
        try {
            $feature = ArticleFeatureModel::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Article feature fetched successfully.',
                'data' => $feature
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Article feature not found.'
            ], 404);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch article feature.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified article feature in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $feature = ArticleFeatureModel::findOrFail($id);

            $request->validate([
                'month_year' => 'nullable|string|max:55',
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string|max:255',
                'img_url' => 'nullable|string',
            ]);

            $feature->update($request->only(['month_year', 'title', 'description', 'img_url']));

            return response()->json([
                'success' => true,
                'message' => 'Article feature updated successfully!',
                'data' => $feature
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Article feature not found.'
            ], 404);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update article feature.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified article feature from storage.
     */
    public function destroy($id)
    {
        try {
            $feature = ArticleFeatureModel::findOrFail($id);
            $feature->delete();

            return response()->json([
                'success' => true,
                'message' => 'Article feature deleted successfully.'
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Article feature not found.'
            ], 404);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete article feature.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
