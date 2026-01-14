<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Comment;
use App\Models\Video;
use App\Models\AudioModel;
use App\Models\Article;
use App\Models\Category;

class AdminUIController extends Controller
{
    /**
     * Admin dashboard statistics
     */
    public function index(): JsonResponse
    {
        try {
            // Cache for 5 minutes
            $data = Cache::remember('admin_dashboard_stats', now()->addMinutes(5), function () {
                return [
                    'users'         => User::count(),
                    'subscriptions' => Subscription::count(),
                    'comments'      => Comment::count(),
                    'videos'        => Video::count(),
                    'audios'        => AudioModel::count(),
                    'articles'      => Article::count(),
                    'categories'    => Category::count(),
                ];
            });

            return response()->json([
                'status' => true,
                'data'   => $data,
            ], 200);

        } catch (\Throwable $e) {
            // Log the actual error for debugging
            Log::error('Admin dashboard stats failed', [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Failed to load dashboard statistics.',
            ], 500);
        }
    }
}
