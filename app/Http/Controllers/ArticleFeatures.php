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
    public function index(Request $request)
    {
        try {
            $search = $request->query('search');
            $monthYear = $request->query('month_year');
            $perPage = $request->query('per_page', 12);

            $query = ArticleFeatureModel::query();

            // 🔍 Search by title or description
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // 📅 Filter by string month_year
            if ($monthYear) {
                $query->where('month_year', 'like', "%{$monthYear}%");
            }

            $features = $query
                ->orderByRaw("STR_TO_DATE(CONCAT(month_year, '-01'), '%Y-%m-%d') DESC")
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Article features fetched successfully.',
                'pagination' => [
                    'current_page' => $features->currentPage(),
                    'last_page' => $features->lastPage(),
                    'per_page' => $features->perPage(),
                    'total' => $features->total(),
                ],
                'data' => $features->items()
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
