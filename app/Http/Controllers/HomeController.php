<?php

namespace App\Http\Controllers;

use App\Models\Interaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Article;
use App\Models\Category;
use App\Http\Resources\ArticleResource;
use App\Http\Resources\CategoryResource;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->input('user');
        $shownArticleIds = collect();

        // Trending Now: Top viewed articles (cached)
        $trending = Cache::remember('home_trending', 60, function () {
            return Article::published()
                ->orderByDesc('view_count')
                ->limit(5)
                ->get();
        });
        $shownArticleIds = $shownArticleIds->merge($trending->pluck('id'));

        // Editor's Picks: Popular articles with view_count > 1000 (cached)
        $editorsPicks = Cache::remember('home_editors_picks', 60, function () use ($shownArticleIds) {
            return Article::published()
                ->where('view_count', '>', 2)
                ->whereNotIn('id', $shownArticleIds)
                ->orderByDesc('view_count')
                ->limit(5)
                ->get();
        });
        $shownArticleIds = $shownArticleIds->merge($editorsPicks->pluck('id'));

        // Newly Published: Latest articles
        $newlyPublished = Article::published()
            ->whereNotIn('id', $shownArticleIds)
            ->orderByDesc('published_at')
            ->limit(5)
            ->get();
        $shownArticleIds = $shownArticleIds->merge($newlyPublished->pluck('id'));

        // Categories (cached)
        $categories = Cache::remember('home_categories', 60, function () {
            return Category::orderBy('name')->get();
        });

        // Most Liked Articles (cached)
        $mostLiked = Cache::remember('home_most_liked', 60, function () {
            return Article::published()
                ->withCount([
                    'interactions as like_count' => function ($query) {
                        $query->where('type', 'like');
                    }
                ])
                ->orderByDesc('like_count')
                ->limit(5)
                ->get();
        });
        $shownArticleIds = $shownArticleIds->merge($mostLiked->pluck('id'));

        // Personalized Sections
        $recommended = collect();
        $fromAuthors = collect();

        if ($userId) {
            $interactions = Interaction::where('user_id', $userId)
                ->whereIn('type', ['like', 'bookmark'])
                ->with(['article.tags', 'article.author'])
                ->get();

            // Tags-based recommendation
            $likedTagIds = $interactions
                ->flatMap(fn($interaction) => optional($interaction->article)->tags->pluck('id') ?? collect())
                ->unique();

            if ($likedTagIds->isNotEmpty()) {
                $recommended = Article::published()
                    ->whereHas('tags', function ($q) use ($likedTagIds) {
                        $q->whereIn('tags.id', $likedTagIds);
                    })
                    ->whereNotIn('id', $shownArticleIds)
                    ->orderByDesc('published_at')
                    ->limit(5)
                    ->get();

                $shownArticleIds = $shownArticleIds->merge($recommended->pluck('id'));
            }

            // From authors you've read
            $authorIds = $interactions
                ->pluck('article.author.id')
                ->unique()
                ->filter();

            if ($authorIds->isNotEmpty()) {
                $fromAuthors = Article::published()
                    ->whereIn('author_id', $authorIds)
                    ->whereNotIn('id', $shownArticleIds)
                    ->orderByDesc('published_at')
                    ->limit(5)
                    ->get();

                $shownArticleIds = $shownArticleIds->merge($fromAuthors->pluck('id'));
            }
        }

        // Prepare final response with resources
        $response = [
            'Trending Now' => ArticleResource::collection($trending),
            'Most Liked' => ArticleResource::collection($mostLiked),
            'Recommended for You' => ArticleResource::collection($recommended),
            'From Authors You Read' => ArticleResource::collection($fromAuthors),
            'Editor\'s Picks' => ArticleResource::collection($editorsPicks),
            'Newly Published' => ArticleResource::collection($newlyPublished),
            'Categories' => CategoryResource::collection($categories),
        ];

        // Filter out empty sections
        $filtered = collect($response)->filter(fn($list) => $list->count() > 0);

        return response()->json($filtered);
    }
}
