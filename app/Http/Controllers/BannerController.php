<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Throwable;

class BannerController extends Controller
{
    /**
     * Get all banners (with optional filters)
     */
    public function index(Request $request)
    {
        try {
            $query = Banner::query();

            // Filters
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            if ($request->filled('position')) {
                $query->where('position', $request->position);
            }

            if ($request->filled('type')) {
                $query->where('bannerable_type', 'App\\Models\\' . ucfirst($request->type));
            }

            $banners = $query
                ->orderByDesc('priority')
                ->paginate(20);

            return response()->json([
                'status' => true,
                'data' => $banners,
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * Get banner by ID
     */
    public function show($id)
    {
        try {
            $banner = Banner::findOrFail($id);

            return response()->json([
                'status' => true,
                'data' => $banner,
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'status' => false,
                'message' => 'Banner not found',
            ], 404);
        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * Create new banner
     */
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'title' => 'required|string|max:255',
                'img' => 'required|string|max:255',
                'mobile_img' => 'nullable|string|max:255',

                'bannerable_id' => 'required|integer',
                'bannerable_type' => 'required|string|max:100',

                'position' => 'nullable|string|max:50',
                'priority' => 'nullable|integer',

                'is_active' => 'boolean',
                'start_at' => 'nullable|date',
                'end_at' => 'nullable|date|after_or_equal:start_at',
            ]);

            // Normalize bannerable_type
            if (!str_contains($data['bannerable_type'], '\\')) {
                $data['bannerable_type'] = 'App\\Models\\' . ucfirst($data['bannerable_type']);
            }

            $banner = Banner::create($data);

            return response()->json([
                'status' => true,
                'message' => 'Banner created successfully',
                'data' => $banner,
            ], 201);
        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * Update banner
     */
    public function update(Request $request, $id)
    {
        try {
            $banner = Banner::findOrFail($id);

            $data = $request->validate([
                'title' => 'sometimes|string|max:255',
                'img' => 'sometimes|string|max:255',
                'mobile_img' => 'nullable|string|max:255',

                'bannerable_id' => 'sometimes|integer',
                'bannerable_type' => 'sometimes|string|max:100',

                'position' => 'nullable|string|max:50',
                'priority' => 'nullable|integer',

                'is_active' => 'boolean',
                'start_at' => 'nullable|date',
                'end_at' => 'nullable|date|after_or_equal:start_at',
            ]);

            if (isset($data['bannerable_type']) && !str_contains($data['bannerable_type'], '\\')) {
                $data['bannerable_type'] = 'App\\Models\\' . ucfirst($data['bannerable_type']);
            }

            $banner->update($data);

            return response()->json([
                'status' => true,
                'message' => 'Banner updated successfully',
                'data' => $banner,
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'status' => false,
                'message' => 'Banner not found',
            ], 404);
        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * Delete banner
     */
    public function destroy($id)
    {
        try {
            $banner = Banner::findOrFail($id);
            $banner->delete();

            return response()->json([
                'status' => true,
                'message' => 'Banner deleted successfully',
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'status' => false,
                'message' => 'Banner not found',
            ], 404);
        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * Search banners
     */
    public function search(Request $request)
    {
        try {
            $request->validate([
                'q' => 'required|string|min:2',
            ]);

            $banners = Banner::where('title', 'LIKE', '%' . $request->q . '%')
                ->orderByDesc('created_at')
                ->paginate(20);

            return response()->json([
                'status' => true,
                'data' => $banners,
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * Common error handler
     */
    private function errorResponse(Throwable $e)
    {
        return response()->json([
            'status' => false,
            'message' => 'Something went wrong',
            'error' => config('app.debug') ? $e->getMessage() : null,
        ], 500);
    }
}
