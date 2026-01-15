<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ArticleReadTime;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Database\QueryException;

class ArticleReadTimeController extends Controller
{
    /**
     * Add reading time for a user and article
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|string',
                'article_id' => 'required|integer',
                'duration_seconds' => 'required|integer|min:1',
                'device_type' => 'nullable|in:mobile,desktop,tablet',
            ]);

            $readTime = $this->handleStoreReadingTime(
                $request->user_id,
                $request->article_id,
                $request->duration_seconds,
                $request->device_type ?? 'mobile'
            );

            return response()->json([
                'success' => true,
                'message' => 'Reading time added successfully',
                'data' => $readTime,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Validation errors
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (QueryException $e) {
            // Database errors
            return response()->json([
                'success' => false,
                'message' => 'Database error',
                'error' => $e->getMessage()
            ], 500);

        } catch (\Exception $e) {
            // General errors
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get total reading time per user per article
     */
    public function getTotal($userId, $articleId)
    {
        try {
            $totalTime = $this->handleGetTotalReadingTime($userId, $articleId);

            return response()->json([
                'success' => true,
                'user_id' => $userId,
                'article_id' => $articleId,
                'total_time_seconds' => $totalTime,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch total reading time',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /***********************
     * Handler Methods
     ***********************/

    private function handleStoreReadingTime(string $userId, int $articleId, int $durationSeconds, string $deviceType)
    {
        $now = Carbon::now();

        return ArticleReadTime::create([
            'user_id' => $userId,
            'article_id' => $articleId,
            'session_id' => Str::uuid()->toString(),
            'start_time' => $now,
            'end_time' => $now,
            'duration_seconds' => $durationSeconds,
            'device_type' => $deviceType,
        ]);
    }

    private function handleGetTotalReadingTime(string $userId, int $articleId): int
    {
        return ArticleReadTime::where('user_id', $userId)
            ->where('article_id', $articleId)
            ->sum('duration_seconds');
    }
}
