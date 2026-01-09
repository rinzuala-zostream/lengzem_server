<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Subscription;
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

            // 🔐 If the article is premium, check subscription
            if ($article->isPremium) {
                if (!$userId) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Please log in to access premium content.',
                    ], 401);
                }

                $activeSubscription = Subscription::where('user_id', $userId)
                    ->where('status', 'active')
                    ->latest('id')
                    ->first();

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
        try {
            // Normalize status to lowercase for consistency with DB enums
            $status = strtolower((string) $request->input('status'));

            // Base rules
            $rules = [
                'title' => 'required|string|max:255',
                'summary' => 'nullable|string',
                'content' => 'required|string',
                'slug' => 'nullable|string|max:255|unique:articles,slug',
                'author_id' => 'nullable|exists:user,id',
                'category_id' => 'nullable|exists:categories,id',
                'status' => 'required|in:Draft,Published,Scheduled,draft,published,scheduled',
                'scheduled_publish_time' => 'nullable|date',
                'cover_image_url' => 'nullable|string', // change to 'url' if always absolute
                'tags' => 'nullable|array',
                'tags.*' => 'exists:tags,id',
                'isCommentable' => 'nullable|boolean',
                'isPremium' => 'nullable|boolean',
                'published_at' => 'nullable|date',
            ];

            $data = $request->validate($rules);

            // Normalize status casing
            $data['status'] = $status; // 'draft' | 'published' | 'scheduled'

            // Auto-generate slug if not provided, ensure uniqueness
            $baseSlug = $data['slug'] ?? Str::slug($data['title']);
            $slug = $baseSlug;
            $i = 1;
            while (Article::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $i++;
            }
            $data['slug'] = $slug;

            // Timestamps logic
            if ($data['status'] === 'published') {
                // Set published_at to now if not provided
                $data['published_at'] = !empty($data['published_at'])
                    ? Carbon::parse($data['published_at'])
                    : now();
                // a published article shouldn't carry a scheduled time
                $data['scheduled_publish_time'] = null;
            } elseif ($data['status'] === 'scheduled') {
                // ensure scheduled_publish_time exists (rule above enforces) and clear published_at
                $data['published_at'] = null;
            } else { // draft
                $data['published_at'] = null;
                $data['scheduled_publish_time'] = null;
            }

            // Default booleans
            $data['isCommentable'] = array_key_exists('isCommentable', $data) ? (bool) $data['isCommentable'] : true;
            $data['isPremium'] = array_key_exists('isPremium', $data) ? (bool) $data['isPremium'] : false;

            /** @var Article $article */
            $article = Article::create($data);

            if (!empty($data['tags'])) {
                $article->tags()->sync($data['tags']);
            }

            // Try FCM only when published
            if ($data['status'] === 'published') {
                try {
                    // Build a concise body from summary or content
                    $bodyText = $data['summary'] ?? Str::limit(strip_tags($data['content']), 140);
                    $fakeRequest = new Request([
                        'type' => 'topic',
                        'recipient' => 'all',
                        'title' => $data['title'],
                        'body' => $bodyText,
                        'image' => $data['cover_image_url'] ?? '',
                        'key' => (string) $article->id, // or slug if you prefer
                    ]);
                    // Assuming $this->fcm is an injected controller/service with ->send()
                    $this->fcm->send($fakeRequest);
                } catch (\Throwable $e) {
                    // Log and continue — don't fail the whole request
                    \Log::warning('FCM send failed for article ' . $article->id . ': ' . $e->getMessage());
                }
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Article created successfully.',
                'data' => $article->load('tags'),
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            if (DB::transactionLevel() > 0)
                DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0)
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
                'author_id' => 'sometimes|exists:user,id',
                'category_id' => 'nullable|exists:categories,id',
                'slug' => 'nullable|string|max:255|unique:articles,slug',
                'isCommentable' => 'nullable|boolean',
                'isPremium' => 'nullable|boolean',
                'status' => 'sometimes|in:Draft,Published,Scheduled',
                'scheduled_publish_time' => 'nullable|date',
                'cover_image_url' => 'nullable|string',
                'tags' => 'nullable|array',
                'tags.*' => 'exists:tags,id',
                'published_at' => 'nullable|date',
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
}
