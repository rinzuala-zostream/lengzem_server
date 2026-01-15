<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ArticleReadTime;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ArticleReadTimeController extends Controller
{
    /**
     * Add reading time for a user and article
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'article_id' => 'required|integer',
            'duration_seconds' => 'required|integer|min:1',
            'device_type' => 'nullable|in:mobile,desktop,tablet',
        ]);

        $now = Carbon::now();

        $readTime = ArticleReadTime::create([
            'user_id' => $request->user_id,
            'article_id' => $request->article_id,
            'session_id' => Str::uuid()->toString(), // generate a unique session id
            'start_time' => $now,
            'end_time' => $now, // same as start_time, since we only log duration
            'duration_seconds' => $request->duration_seconds,
            'device_type' => $request->device_type ?? 'mobile',
        ]);

        return response()->json([
            'message' => 'Reading time added successfully',
            'data' => $readTime,
        ]);
    }

    /**
     * Get total reading time per user per article
     */
    public function getTotal($userId, $articleId)
    {
        $totalTime = ArticleReadTime::where('user_id', $userId)
            ->where('article_id', $articleId)
            ->sum('duration_seconds');

        return response()->json([
            'user_id' => $userId,
            'article_id' => $articleId,
            'total_time_seconds' => $totalTime,
        ]);
    }
}
