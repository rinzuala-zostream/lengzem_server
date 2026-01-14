<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
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
                // Basic counts
                $basicStats = [
                    'users'         => User::count(),
                    'subscriptions' => Subscription::count(),
                    'comments'      => Comment::count(),
                    'videos'        => Video::count(),
                    'audios'        => AudioModel::count(),
                    'articles'      => Article::count(),
                    'categories'    => Category::count(),
                ];

                // User growth statistics (last 30 days)
                $userGrowth = $this->getUserGrowthStats();
                
                // New users in last 7 days
                $newUsersThisWeek = User::where('created_at', '>=', Carbon::now()->subDays(7))->count();
                
                // Active users (users with some activity - has articles or comments)
                $activeUsers = User::whereHas('articles')->orWhereHas('comments', function ($query) {
                    $query->where('created_at', '>=', Carbon::now()->subDays(30));
                })->count();

                return array_merge($basicStats, [
                    'user_growth' => $userGrowth,
                    'new_users_this_week' => $newUsersThisWeek,
                    'active_users' => $activeUsers,
                    'users_per_day' => $this->getUsersPerDay(30), // Last 30 days
                    'current_date' => Carbon::now()->toDateString(),
                ]);
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

    /**
     * Get user growth statistics
     */
    private function getUserGrowthStats(): array
    {
        $today = Carbon::today();
        $lastMonth = Carbon::today()->subDays(30);
        
        // Users created today
        $usersToday = User::whereDate('created_at', $today)->count();
        
        // Users created yesterday
        $usersYesterday = User::whereDate('created_at', $today->copy()->subDay())->count();
        
        // Users created this week
        $usersThisWeek = User::where('created_at', '>=', $today->copy()->startOfWeek())->count();
        
        // Users created last week
        $usersLastWeek = User::whereBetween('created_at', [
            $today->copy()->subWeek()->startOfWeek(),
            $today->copy()->subWeek()->endOfWeek()
        ])->count();
        
        // Users created this month
        $usersThisMonth = User::where('created_at', '>=', $today->copy()->startOfMonth())->count();
        
        // Users created last month
        $usersLastMonth = User::whereBetween('created_at', [
            $today->copy()->subMonth()->startOfMonth(),
            $today->copy()->subMonth()->endOfMonth()
        ])->count();

        // Calculate growth percentages
        $weekGrowth = $usersLastWeek > 0 
            ? (($usersThisWeek - $usersLastWeek) / $usersLastWeek) * 100 
            : ($usersThisWeek > 0 ? 100 : 0);
        
        $monthGrowth = $usersLastMonth > 0 
            ? (($usersThisMonth - $usersLastMonth) / $usersLastMonth) * 100 
            : ($usersThisMonth > 0 ? 100 : 0);

        return [
            'today' => $usersToday,
            'yesterday' => $usersYesterday,
            'this_week' => $usersThisWeek,
            'last_week' => $usersLastWeek,
            'this_month' => $usersThisMonth,
            'last_month' => $usersLastMonth,
            'week_growth_percentage' => round($weekGrowth, 1),
            'month_growth_percentage' => round($monthGrowth, 1),
            'daily_growth_percentage' => $usersYesterday > 0 
                ? round((($usersToday - $usersYesterday) / $usersYesterday) * 100, 1) 
                : ($usersToday > 0 ? 100 : 0),
        ];
    }

    /**
     * Get users per day for the last N days
     */
    private function getUsersPerDay(int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        
        // Get daily user counts
        $dailyUsers = User::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
        ->whereBetween('created_at', [$startDate, $endDate])
        ->groupBy('date')
        ->orderBy('date', 'asc')
        ->get()
        ->keyBy('date');

        // Fill in missing dates with 0
        $result = [];
        $currentDate = $startDate->copy();
        
        while ($currentDate <= $endDate) {
            $dateString = $currentDate->toDateString();
            $result[] = [
                'date' => $dateString,
                'count' => $dailyUsers->has($dateString) ? $dailyUsers[$dateString]->count : 0,
                'formatted_date' => $currentDate->format('M j'),
                'day_name' => $currentDate->format('D'),
            ];
            $currentDate->addDay();
        }

        return $result;
    }

    /**
     * Get detailed user statistics with date range
     */
    public function getUserStatistics(): JsonResponse
    {
        try {
            $data = Cache::remember('admin_user_stats', now()->addMinutes(5), function () {
                $days = request()->get('days', 30); // Default to 30 days
                
                return [
                    'users_per_day' => $this->getUsersPerDay($days),
                    'total_users' => User::count(),
                    'new_users_today' => User::whereDate('created_at', Carbon::today())->count(),
                    'active_users_count' => User::whereHas('articles')->orWhereHas('comments', function ($query) {
                        $query->where('created_at', '>=', Carbon::now()->subDays(30));
                    })->count(),
                    'user_roles' => [
                        'admin' => User::where('role', 'admin')->count(),
                        'editor' => User::where('role', 'editor')->count(),
                        'user' => User::where('role', 'user')->count(),
                    ],
                    'time_period' => $days . ' days',
                    'from_date' => Carbon::now()->subDays($days)->toDateString(),
                    'to_date' => Carbon::now()->toDateString(),
                ];
            });

            return response()->json([
                'status' => true,
                'data' => $data,
            ], 200);

        } catch (\Throwable $e) {
            Log::error('User statistics failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to load user statistics.',
            ], 500);
        }
    }
}