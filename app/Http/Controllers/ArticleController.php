<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ArticleController extends Controller
{

    protected $subscriptionModel;

    public function __construct(Subscription $subscriptionModel)
    {
        $this->subscriptionModel = $subscriptionModel;
    }

    public function index(Request $request)
    {
        try {
            $query = Article::withCount('comments')->with(['author', 'category', 'tags']);

            // Filtering by category or tag slug
            if ($request->has('category')) {
                $query->whereHas('category', function ($q) use ($request) {
                    $q->where('slug', $request->category);
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
                    ->where('end_date', '>=', now())
                    ->latest('id')
                    ->first();

                if (!$activeSubscription) {
                    return response()->json([
                        'status' => false,
                        'message' => 'You need an active subscription to access this article.',
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
            $data = $request->validate([
                'title' => 'required|string|max:255',
                'summary' => 'nullable|string',
                'content' => 'required|string',
                'slug' => 'nullable|string|max:255|unique:articles,slug',
                'author_id' => 'required|exists:user,id',
                'category_id' => 'nullable|exists:categories,id',
                'status' => 'required|in:Draft,Published,Scheduled',
                'scheduled_publish_time' => 'nullable|date',
                'cover_image_url' => 'nullable|string',
                'tags' => 'nullable|array',
                'tags.*' => 'exists:tags,id',
                'isCommentable' => 'nullable|boolean',
                'isPremium' => 'nullable|boolean',
                'published_at' => 'nullable|date',
            ]);

            // Set published_at to now if not provided
            if (empty($data['published_at'])) {
                $data['published_at'] = now();
            }

            // Auto-generate slug if not provided
            $data['slug'] = $data['slug'] ?? Str::slug($data['title']);

            $article = Article::create($data);

            if (!empty($data['tags'])) {
                $article->tags()->sync($data['tags']);
            }

            return response()->json([
                'status' => true,
                'message' => 'Article created successfully.',
                'data' => $article->load('tags'),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
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
}
