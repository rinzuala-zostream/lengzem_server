<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\RedeemCode;
use App\Models\Subscription;
use App\Models\Notification;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ArticleController extends Controller
{

    protected $subscriptionModel;

    protected $fcm;

    public function __construct(Subscription $subscriptionModel, FCMNotificationController $fCMNotificationController)
    {
        $this->subscriptionModel = $subscriptionModel;
        $this->fcm = $fCMNotificationController;
    }

    public function index(Request $request)
    {
        try {
            $query = Article::withCount('comments')->with(['author', 'category', 'tags']);

            // Filtering by category or tag slug
            if ($request->has('category')) {
                $query->whereHas('category', function ($q) use ($request) {
                    $q->where('slug', Str::slug($request->category));

                });
            }

            if ($request->has('tag')) {
                $query->whereHas('tags', function ($q) use ($request) {
                    $q->where('slug', $request->tag);
                });
            }

            $articles = $query->paginate(10);

            return response()->json([
                'status' => true,
                'message' => 'Articles retrieved successfully.',
                'data' => $articles,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve articles.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $userId = $request->query('uid'); // Get user ID from query

            $article = Article::published()
                ->with(['author', 'category', 'tags', 'media'])
                ->withCount('comments')
                ->findOrFail($id);

            // 🔐 If the article is premium, check subscription or redeem benefit
            if ($article->isPremium) {
                if (!$userId) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Please log in to access premium content.',
                    ], 401);
                }

                // Check active subscription
                $activeSubscription = Subscription::where('user_id', $userId)
                    ->where('status', 'active')
                    ->latest('id')
                    ->first();

                // 🪄 If no subscription, check redeem benefit
                if (!$activeSubscription) {
                    $activeRedeem = RedeemCode::where('user_id', $userId)
                        ->where('is_active', true)
                        ->whereDate('benefit_end_month', '>=', now())
                        ->where(function ($query) {
                            $query->whereNull('expire_date')
                                ->orWhere('expire_date', '>=', now());
                        })
                        ->first();

                    // Treat redeem benefit as an active subscription
                    if ($activeRedeem) {
                        $activeSubscription = true;
                    }
                }

                if (!$activeSubscription) {
                    return response()->json([
                        'status' => false,
                        'message' => 'He thuziak chhiar tur chuan subscription i neih a ngai, Lengzem i subscribe dawm em?.',
                    ], 403);
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Article retrieved successfully.',
                'data' => $article,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve article.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
{
    DB::beginTransaction();

    try {
        // Normalize status
        $status = strtolower((string) $request->input('status'));

        $rules = [
            'title' => 'required|string|max:255',
            'summary' => 'nullable|string',
            'content' => 'required|string',
            'slug' => 'nullable|string|max:255|unique:articles,slug',
            'author_id' => 'nullable|exists:user,id',
            'isContributor' => 'nullable|boolean',
            'contributor' => 'nullable|string',
            'contact' => 'nullable|string|max:50',
            'category_id' => 'nullable|exists:categories,id',
            'status' => 'required|in:Draft,Published,Scheduled,draft,published,scheduled',
            'isApproved' => 'nullable|boolean',
            'scheduled_publish_time' => 'nullable|date',
            'cover_image_url' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
            'isCommentable' => 'nullable|boolean',
            'isPremium' => 'nullable|boolean',
            'published_at' => 'nullable|date',
            'isNotify' => 'nullable|boolean',
        ];

        $data = $request->validate($rules);

        // Normalize status
        $data['status'] = $status;

        // Slug generation
        $baseSlug = $data['slug'] ?? Str::slug($data['title']);
        $slug = $baseSlug;
        $i = 1;
        while (Article::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $i++;
        }
        $data['slug'] = $slug;

        // Force approval false for contributor articles
        if (!empty($data['isContributor']) && $data['isContributor'] === true) {
            $data['isApproved'] = false;
        }

        // Status timestamps
        if ($data['status'] === 'published') {
            $data['published_at'] = !empty($data['published_at'])
                ? Carbon::parse($data['published_at'])
                : now();
            $data['scheduled_publish_time'] = null;
        } elseif ($data['status'] === 'scheduled') {
            $data['published_at'] = null;
        } else {
            $data['published_at'] = null;
            $data['scheduled_publish_time'] = null;
        }

        // Defaults
        $data['isCommentable'] = $data['isCommentable'] ?? true;
        $data['isPremium'] = $data['isPremium'] ?? false;

        /** @var Article $article */
        $article = Article::create($data);

        // Tags
        if (!empty($data['tags'])) {
            $article->tags()->sync($data['tags']);
        }

        /**
         * 🔔 Create admin approval notification
         * ONLY for contributor articles
         */
        if (
            !empty($article->isContributor) &&
            !$article->isApproved
        ) {
            Notification::create([
                'notifiable_type' => Article::class,
                'notifiable_id'   => $article->id,
                'actor_id'        => auth()->id(),
                'action'          => 'article_created',
                'message'         => 'New contributor article pending approval',
                'target_role'     => 'admin',
                'status'          => 'pending',
            ]);
        }

        /**
         * 📢 FCM (unchanged – separate concern)
         */
        if ($data['status'] === 'published' && !empty($data['isNotify'])) {
            try {
                $bodyText = $data['summary']
                    ?? Str::limit(strip_tags($data['content']), 140);

                $fakeRequest = new Request([
                    'type' => 'topic',
                    'recipient' => 'all',
                    'title' => $data['title'],
                    'body' => $bodyText,
                    'image' => $data['cover_image_url'] ?? '',
                    'key' => (string) $article->id,
                ]);

                $this->fcm->send($fakeRequest);

            } catch (\Throwable $e) {
                \Log::warning(
                    'FCM send failed for article ' . $article->id . ': ' . $e->getMessage()
                );
            }
        }

        DB::commit();

        return response()->json([
            'status' => true,
            'message' => 'Article created successfully.',
            'data' => $article->load('tags'),
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        DB::rollBack();
        return response()->json([
            'status' => false,
            'message' => 'Validation failed.',
            'errors' => $e->errors(),
        ], 422);

    } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json([
            'status' => false,
            'message' => 'Failed to create article.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

    public function update(Request $request, $id)
    {
        try {
            $article = Article::findOrFail($id);

            $data = $request->validate([
                'title' => 'sometimes|string|max:255',
                'summary' => 'nullable|string',
                'content' => 'sometimes|string',
                'author_id' => 'nullable|exists:user,id',
                'isContributor' => 'nullable|boolean',
                'contributor' => 'nullable|exists:user,id',
                'contact' => 'nullable|string|max:50',
                'category_id' => 'nullable|exists:categories,id',
                'slug' => 'nullable|string|max:255|unique:articles,slug',
                'isCommentable' => 'nullable|boolean',
                'isPremium' => 'nullable|boolean',
                'status' => 'sometimes|in:Draft,Published,Scheduled',
                'isApproved' => 'nullable|boolean',
                'scheduled_publish_time' => 'nullable|date',
                'cover_image_url' => 'nullable|string',
                'tags' => 'nullable|array',
                'tags.*' => 'exists:tags,id',
                'published_at' => 'nullable|date',
                'isNotify' => 'nullable|boolean',
            ]);

            if (isset($data['title'])) {
                $data['slug'] = Str::slug($data['title']);
            }

            $article->update($data);

            if (array_key_exists('tags', $data)) {
                $article->tags()->sync($data['tags'] ?? []);
            }

            return response()->json([
                'status' => true,
                'message' => 'Article updated successfully',
                'data' => $article->load('tags'),
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
                'message' => 'Failed to update article.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function publicStore(Request $request)
{
    DB::beginTransaction();

    try {
        // Normalize status
        $status = strtolower((string) $request->input('status'));

        $rules = [
            'title' => 'required|string|max:255',
            'summary' => 'nullable|string',
            'content' => 'required|string',
            'slug' => 'nullable|string|max:255|unique:articles,slug',
            'author_id' => 'nullable|exists:user,id',
            'isContributor' => 'nullable|boolean',
            'contributor' => 'nullable|string',
            'contact' => 'nullable|string|max:50',
            'category_id' => 'nullable|exists:categories,id',
            'status' => 'required|in:Draft,Published,Scheduled,draft,published,scheduled',
            'isApproved' => 'nullable|boolean',
            'scheduled_publish_time' => 'nullable|date',
            'cover_image_url' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
            'isCommentable' => 'nullable|boolean',
            'isPremium' => 'nullable|boolean',
            'published_at' => 'nullable|date',
            'isNotify' => 'nullable|boolean',
        ];

        $data = $request->validate($rules);

        // Normalize status
        $data['status'] = $status;

        // Slug generation
        $baseSlug = $data['slug'] ?? Str::slug($data['title']);
        $slug = $baseSlug;
        $i = 1;
        while (Article::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $i++;
        }
        $data['slug'] = $slug;

        // Force approval false for contributor articles
        if (!empty($data['isContributor']) && $data['isContributor'] === true) {
            $data['isApproved'] = false;
        }

        // Status timestamps
        if ($data['status'] === 'published') {
            $data['published_at'] = !empty($data['published_at'])
                ? Carbon::parse($data['published_at'])
                : now();
            $data['scheduled_publish_time'] = null;
        } elseif ($data['status'] === 'scheduled') {
            $data['published_at'] = null;
        } else {
            $data['published_at'] = null;
            $data['scheduled_publish_time'] = null;
        }

        // Defaults
        $data['isCommentable'] = $data['isCommentable'] ?? true;
        $data['isPremium'] = $data['isPremium'] ?? false;

        /** @var Article $article */
        $article = Article::create($data);

        // Tags
        if (!empty($data['tags'])) {
            $article->tags()->sync($data['tags']);
        }

        /**
         * 🔔 Create admin approval notification
         * ONLY for contributor articles
         */
        if (
            !empty($article->isContributor) &&
            !$article->isApproved
        ) {
            Notification::create([
                'notifiable_type' => Article::class,
                'notifiable_id'   => $article->id,
                'actor_id'        => auth()->id(),
                'action'          => 'article_created',
                'message'         => 'New contributor article pending approval',
                'target_role'     => 'admin',
                'status'          => 'pending',
            ]);
        }

        /**
         * 📢 FCM (unchanged – separate concern)
         */
        if ($data['status'] === 'published' && !empty($data['isNotify'])) {
            try {
                $bodyText = $data['summary']
                    ?? Str::limit(strip_tags($data['content']), 140);

                $fakeRequest = new Request([
                    'type' => 'topic',
                    'recipient' => 'all',
                    'title' => $data['title'],
                    'body' => $bodyText,
                    'image' => $data['cover_image_url'] ?? '',
                    'key' => (string) $article->id,
                ]);

                $this->fcm->send($fakeRequest);

            } catch (\Throwable $e) {
                \Log::warning(
                    'FCM send failed for article ' . $article->id . ': ' . $e->getMessage()
                );
            }
        }

        DB::commit();

        return response()->json([
            'status' => true,
            'message' => 'Article created successfully.',
            'data' => $article->load('tags'),
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        DB::rollBack();
        return response()->json([
            'status' => false,
            'message' => 'Validation failed.',
            'errors' => $e->errors(),
        ], 422);

    } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json([
            'status' => false,
            'message' => 'Failed to create article.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

    public function destroy($id)
    {
        try {
            $article = Article::findOrFail($id);
            $article->delete();

            return response()->json([
                'status' => true,
                'message' => 'Article deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete article.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function search(Request $request)
    {
        try {
            $query = Article::with(['author', 'category', 'tags'])
                ->withCount('comments');

            // Build message text
            $searchText = $request->filled('q') ? $request->q : 'all articles';

            /**
             * 🔍 TEXT SEARCH
             */
            if ($request->filled('q')) {
                $q = $request->q;
                $query->where(function ($x) use ($q) {
                    $x->where('title', 'LIKE', "%$q%")
                        ->orWhere('summary', 'LIKE', "%$q%");
                });
            }

            /**
             * 🗂 CATEGORY FILTER
             */
            if ($request->filled('category')) {
                $query->whereHas('category', function ($q) use ($request) {
                    $q->where('slug', Str::slug($request->category));
                });

                $searchText .= " | category: {$request->category}";
            }

            /**
             * 🏷 TAG FILTER
             */
            if ($request->filled('tag')) {
                $query->whereHas('tags', function ($q) use ($request) {
                    $q->where('slug', $request->tag);
                });

                $searchText .= " | tag: {$request->tag}";
            }

            /**
             * ⭐ PREMIUM FILTER
             */
            if ($request->filled('premium')) {
                $query->where('isPremium', filter_var($request->premium, FILTER_VALIDATE_BOOLEAN));
                $searchText .= " | premium: {$request->premium}";
            }

            /**
             * 📅 YEAR / BETWEEN YEARS FIXED LOGIC
             */
            $hasFrom = $request->filled('from');
            $hasTo = $request->filled('to');

            // BOTH from and to → BETWEEN filter
            if ($hasFrom && $hasTo) {
                $from = intval($request->from);
                $to = intval($request->to);

                $query->whereBetween(DB::raw('YEAR(published_at)'), [$from, $to]);
                $searchText .= " | years: {$from}-{$to}";
            }

            // ONLY from → exact year = from
            else if ($hasFrom && !$hasTo) {
                $year = intval($request->from);

                $query->whereYear('published_at', $year);
                $searchText .= " | year: {$year}";
            }

            // ONLY to → exact year = to
            else if (!$hasFrom && $hasTo) {
                $year = intval($request->to);

                $query->whereYear('published_at', $year);
                $searchText .= " | year: {$year}";
            }

            /**
             * 📄 STATUS
             */
            if ($request->filled('status')) {
                $query->where('status', strtolower($request->status));
                $searchText .= " | status: {$request->status}";
            }

            /**
             * 🕒 SORTING
             */
            $sort = $request->get('sort', 'latest');
            if ($sort === 'oldest') {
                $query->orderBy('published_at', 'asc');
            } else {
                $query->orderBy('published_at', 'desc');
            }

            /**
             * ♾ ALL RESULTS
             */
            if ($request->get('mode') === 'all') {
                return response()->json([
                    'status' => true,
                    'message' => "Search for: {$searchText}",
                    'data' => $query->get(),
                ]);
            }

            /**
             * 📄 PAGINATED RESULTS
             */
            $articles = $query->paginate($request->get('per_page', 10));

            return response()->json([
                'status' => true,
                'message' => "Search for: {$searchText}",
                'data' => $articles,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Search failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function adminShow(Request $request, $id)
    {
        try {
            $article = Article::with([
                'author',
                'category',
                'tags',
                'media',
                'comments',
            ])
                ->withCount('comments')
                ->findOrFail($id);

            return response()->json([
                'status' => true,
                'message' => 'Article retrieved successfully (admin).',
                'data' => $article,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Article not found.',
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve article.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
