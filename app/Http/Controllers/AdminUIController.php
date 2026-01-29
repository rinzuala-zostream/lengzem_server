<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Comment;
use App\Models\Video;
use App\Models\AudioModel;
use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;

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
                
                // Revenue statistics
                $revenueStats = $this->getRevenueStats();
                
                // New users in last 7 days
                $newUsersThisWeek = User::where('created_at', '>=', Carbon::now()->subDays(7))->count();
                
                // Active users (users with some activity - has articles or comments)
                $activeUsers = User::where('created_at', '>=', Carbon::now()->subDays(30))->count();

                return array_merge($basicStats, [
                    'user_growth' => $userGrowth,
                    'revenue_stats' => $revenueStats,
                    'new_users_this_week' => $newUsersThisWeek,
                    'active_users' => $activeUsers,
                    'users_per_day' => $this->getUsersPerDay(30), // Last 30 days
                    'revenue_per_day' => $this->getRevenuePerDay(30), // Last 30 days
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
                'message' => $e->getMessage(),
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
     * Get revenue statistics
     */
    private function getRevenueStats(): array
{
    $today = Carbon::today();

    // Base query for active subscriptions
    $activeSubscriptions = Subscription::where('status', 'active');

    // Today's revenue
    $revenueToday = (clone $activeSubscriptions)
        ->whereDate('start_date', $today)
        ->sum('amount');

    // Yesterday's revenue
    $revenueYesterday = (clone $activeSubscriptions)
        ->whereDate('start_date', $today->copy()->subDay())
        ->sum('amount');

    // This week's revenue
    $revenueThisWeek = (clone $activeSubscriptions)
        ->where('start_date', '>=', $today->copy()->startOfWeek())
        ->sum('amount');

    // Last week's revenue
    $revenueLastWeek = (clone $activeSubscriptions)
        ->whereBetween('start_date', [
            $today->copy()->subWeek()->startOfWeek(),
            $today->copy()->subWeek()->endOfWeek()
        ])
        ->sum('amount');

    // This month's revenue
    $revenueThisMonth = (clone $activeSubscriptions)
        ->where('start_date', '>=', $today->copy()->startOfMonth())
        ->sum('amount');

    // Last month's revenue
    $revenueLastMonth = (clone $activeSubscriptions)
        ->whereBetween('start_date', [
            $today->copy()->subMonth()->startOfMonth(),
            $today->copy()->subMonth()->endOfMonth()
        ])
        ->sum('amount');

    // This year's revenue
    $revenueThisYear = (clone $activeSubscriptions)
        ->where('start_date', '>=', $today->copy()->startOfYear())
        ->sum('amount');

    // Total revenue (all time)
    $revenueTotal = (clone $activeSubscriptions)->sum('amount');

    // Growth calculations
    $dailyGrowth = $revenueYesterday > 0
        ? (($revenueToday - $revenueYesterday) / $revenueYesterday) * 100
        : ($revenueToday > 0 ? 100 : 0);

    $weekGrowth = $revenueLastWeek > 0
        ? (($revenueThisWeek - $revenueLastWeek) / $revenueLastWeek) * 100
        : ($revenueThisWeek > 0 ? 100 : 0);

    $monthGrowth = $revenueLastMonth > 0
        ? (($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100
        : ($revenueThisMonth > 0 ? 100 : 0);

    return [
        'today' => (float) $revenueToday,
        'yesterday' => (float) $revenueYesterday,
        'this_week' => (float) $revenueThisWeek,
        'last_week' => (float) $revenueLastWeek,
        'this_month' => (float) $revenueThisMonth,
        'last_month' => (float) $revenueLastMonth,
        'this_year' => (float) $revenueThisYear,
        'total' => (float) $revenueTotal,
        'daily_growth_percentage' => round($dailyGrowth, 1),
        'weekly_growth_percentage' => round($weekGrowth, 1),
        'monthly_growth_percentage' => round($monthGrowth, 1),
        'formatted' => [
            'today' => '₹' . number_format($revenueToday, 2),
            'yesterday' => '₹' . number_format($revenueYesterday, 2),
            'this_week' => '₹' . number_format($revenueThisWeek, 2),
            'last_week' => '₹' . number_format($revenueLastWeek, 2),
            'this_month' => '₹' . number_format($revenueThisMonth, 2),
            'last_month' => '₹' . number_format($revenueLastMonth, 2),
            'this_year' => '₹' . number_format($revenueThisYear, 2),
            'total' => '₹' . number_format($revenueTotal, 2),
        ]
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
     * Get revenue per day for the last N days
     */
    private function getRevenuePerDay(int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        
        // Get daily revenue
        $dailyRevenue = Subscription::select(
            DB::raw('DATE(start_date) as date'),
            DB::raw('SUM(amount) as amount')
        )
        ->whereBetween('start_date', [$startDate, $endDate])
        ->whereNotNull('amount')
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
                'amount' => $dailyRevenue->has($dateString) ? floatval($dailyRevenue[$dateString]->amount) : 0,
                'formatted_date' => $currentDate->format('M j'),
                'day_name' => $currentDate->format('D'),
                'formatted_amount' => '₹' . number_format($dailyRevenue->has($dateString) ? $dailyRevenue[$dateString]->amount : 0, 2),
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
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get detailed revenue statistics with date range
     */
    public function getRevenueStatistics(): JsonResponse
    {
        try {
            $data = Cache::remember('admin_revenue_stats', now()->addMinutes(5), function () {
                $days = request()->get('days', 30); // Default to 30 days
                
                return [
                    'revenue_per_day' => $this->getRevenuePerDay($days),
                    'revenue_stats' => $this->getRevenueStats(),
                    'subscription_stats' => [
                        'active' => Subscription::where('status', 'active')->count(),
                        'canceled' => Subscription::where('status', 'canceled')->count(),
                        'expired' => Subscription::where('status', 'expired')->count(),
                        'pending' => Subscription::where('status', 'pending')->count(),
                    ],
                    'average_revenue_per_user' => $this->getAverageRevenuePerUser(),
                    'top_plans' => $this->getTopSubscriptionPlans(),
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
            Log::error('Revenue statistics failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get average revenue per user
     */
    private function getAverageRevenuePerUser(): array
    {
        $totalUsers = User::count();
        $totalRevenue = Subscription::sum('amount');
        
        if ($totalUsers > 0) {
            $average = $totalRevenue / $totalUsers;
        } else {
            $average = 0;
        }
        
        // Average revenue per paying user
        $payingUsers = Subscription::distinct('user_id')->count('user_id');
        if ($payingUsers > 0) {
            $averagePerPayingUser = $totalRevenue / $payingUsers;
        } else {
            $averagePerPayingUser = 0;
        }
        
        return [
            'all_users' => floatval($average),
            'paying_users' => floatval($averagePerPayingUser),
            'formatted' => [
                'all_users' => '₹' . number_format($average, 2),
                'paying_users' => '₹' . number_format($averagePerPayingUser, 2),
            ],
            'paying_users_count' => $payingUsers,
            'conversion_rate' => $totalUsers > 0 ? round(($payingUsers / $totalUsers) * 100, 1) : 0,
        ];
    }

    /**
     * Get top subscription plans by revenue
     */
    private function getTopSubscriptionPlans(int $limit = 5): array
    {
        return Subscription::select(
            'subscription_plan_id',
            DB::raw('COUNT(*) as subscription_count'),
            DB::raw('SUM(amount) as total_revenue'),
            DB::raw('AVG(amount) as average_amount')
        )
        ->with('plan')
        ->groupBy('subscription_plan_id')
        ->orderBy('total_revenue', 'desc')
        ->limit($limit)
        ->get()
        ->map(function ($subscription) {
            return [
                'plan_id' => $subscription->subscription_plan_id,
                'plan_name' => $subscription->plan ? $subscription->plan->name : 'Unknown Plan',
                'subscription_count' => $subscription->subscription_count,
                'total_revenue' => floatval($subscription->total_revenue),
                'average_amount' => floatval($subscription->average_amount),
                'formatted_total_revenue' => '₹' . number_format($subscription->total_revenue, 2),
                'formatted_average_amount' => '₹' . number_format($subscription->average_amount, 2),
            ];
        })
        ->toArray();
    }

    /**
     * Get revenue breakdown by time period
     */
    public function getRevenueBreakdown(string $period = 'monthly'): JsonResponse
    {
        try {
            $data = Cache::remember("admin_revenue_breakdown_{$period}", now()->addMinutes(5), function () use ($period) {
                switch ($period) {
                    case 'daily':
                        $groupBy = DB::raw('DATE(start_date)');
                        $format = 'M j, Y';
                        $limit = 30;
                        break;
                    case 'weekly':
                        $groupBy = DB::raw('YEARWEEK(start_date)');
                        $format = 'W\k Y';
                        $limit = 12;
                        break;
                    case 'monthly':
                    default:
                        $groupBy = DB::raw('DATE_FORMAT(start_date, "%Y-%m")');
                        $format = 'M Y';
                        $limit = 12;
                        break;
                    case 'yearly':
                        $groupBy = DB::raw('YEAR(start_date)');
                        $format = 'Y';
                        $limit = 5;
                        break;
                }
                
                $revenueBreakdown = Subscription::select(
                    $groupBy . ' as period',
                    DB::raw('SUM(amount) as total_revenue'),
                    DB::raw('COUNT(*) as subscription_count')
                )
                ->whereNotNull('amount')
                ->groupBy('period')
                ->orderBy('period', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($item) use ($format, $period) {
                    $periodLabel = $this->formatPeriod($item->period, $period);
                    return [
                        'period' => $periodLabel,
                        'total_revenue' => floatval($item->total_revenue),
                        'subscription_count' => $item->subscription_count,
                        'formatted_total_revenue' => '₹' . number_format($item->total_revenue, 2),
                        'average_revenue' => $item->subscription_count > 0 ? floatval($item->total_revenue / $item->subscription_count) : 0,
                    ];
                })
                ->values()
                ->toArray();

                return [
                    'breakdown' => $revenueBreakdown,
                    'period' => $period,
                    'total' => array_sum(array_column($revenueBreakdown, 'total_revenue')),
                    'formatted_total' => '₹' . number_format(array_sum(array_column($revenueBreakdown, 'total_revenue')), 2),
                ];
            });

            return response()->json([
                'status' => true,
                'data' => $data,
            ], 200);

        } catch (\Throwable $e) {
            Log::error("Revenue breakdown failed for period: {$period}", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Format period label based on period type
     */
    private function formatPeriod($periodValue, string $periodType): string
    {
        switch ($periodType) {
            case 'daily':
                return Carbon::parse($periodValue)->format('M j, Y');
            case 'weekly':
                $year = substr($periodValue, 0, 4);
                $week = substr($periodValue, 4);
                return "Week {$week}, {$year}";
            case 'monthly':
                return Carbon::createFromFormat('Y-m', $periodValue)->format('M Y');
            case 'yearly':
                return $periodValue;
            default:
                return $periodValue;
        }
    }

    /**
     * Article add na mi tur
     */
    public function articleAddRes()
{
    // Users with role admin OR editor
    $users = User::whereIn('role', ['admin', 'editor'])->get();

    // All categories
    $categories = Category::all();

    // All tags
    $tags = Tag::all();

    return response()->json([
        'users' => $users,
        'categories' => $categories,
        'tags' => $tags,
    ]);
}
}