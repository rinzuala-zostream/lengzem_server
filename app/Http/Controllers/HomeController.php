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

        // Trending Now
        $trending = Cache::remember('home_trending', 60, function () {
            return Article::published()
                ->with(['author', 'category', 'tags'])
                ->orderByDesc('view_count')
                ->limit(5)
                ->get();
        });
        $shownArticleIds->push(...$trending->pluck('id'));

        // Editor's Picks
        $editorsPicks = Cache::remember('home_editors_picks', 60, function () use ($shownArticleIds) {
            return Article::published()
                ->with(['author', 'category', 'tags'])
                ->where('view_count', '>', 1000)
                ->whereNotIn('id', $shownArticleIds)
                ->orderByDesc('view_count')
                ->limit(5)
                ->get();
        });
        $shownArticleIds->push(...$editorsPicks->pluck('id'));

        // Newly Published
        $newlyPublished = Article::published()
            ->with(['author', 'category', 'tags'])
            ->whereNotIn('id', $shownArticleIds)
            ->orderByDesc('published_at')
            ->limit(5)
            ->get();
        $shownArticleIds->push(...$newlyPublished->pluck('id'));

        // Most Liked
        $mostLiked = Cache::remember('home_most_liked', 60, function () {
            return Article::published()
                ->with(['author', 'category', 'tags'])
                ->withCount([
                    'interactions as like_count' => fn($query) => $query->where('type', 'like')
                ])
                ->orderByDesc('like_count')
                ->limit(5)
                ->get();
        });
        $shownArticleIds->push(...$mostLiked->pluck('id'));

        // Categories (if used somewhere)
        $categories = Cache::remember('home_categories', 60, function () {
            return Category::orderBy('name')->get();
        });

        // Personalized
        $recommended = collect();
        $fromAuthors = collect();

        if ($userId) {
            $interactions = Interaction::where('user_id', $userId)
                ->whereIn('type', ['like', 'bookmark'])
                ->with(['article.tags', 'article.author'])
                ->get();

            $likedTagIds = $interactions
                ->flatMap(fn($i) => optional($i->article)->tags->pluck('id') ?? collect())
                ->unique();

            if ($likedTagIds->isNotEmpty()) {
                $recommended = Article::published()
                    ->with(['author', 'category', 'tags'])
                    ->whereHas('tags', fn($q) => $q->whereIn('tags.id', $likedTagIds))
                    ->whereNotIn('id', $shownArticleIds)
                    ->orderByDesc('published_at')
                    ->limit(5)
                    ->get();
                $shownArticleIds->push(...$recommended->pluck('id'));
            }

            $authorIds = $interactions
                ->pluck('article.author.id')
                ->filter()
                ->unique();

            if ($authorIds->isNotEmpty()) {
                $fromAuthors = Article::published()
                    ->with(['author', 'category', 'tags'])
                    ->whereIn('author_id', $authorIds)
                    ->whereNotIn('id', $shownArticleIds)
                    ->orderByDesc('published_at')
                    ->limit(5)
                    ->get();
                $shownArticleIds->push(...$fromAuthors->pluck('id'));
            }
        }

        // News Nawi (latest articles from 'Nawi' category)
        $newsNawi = Article::published()
            ->with(['author', 'category', 'tags'])
            ->whereHas('category', fn($q) => $q->where('name', 'Nawi'))
            ->whereNotIn('id', $shownArticleIds)
            ->orderByDesc('published_at')
            ->limit(5)
            ->get();
        $shownArticleIds->push(...$newsNawi->pluck('id'));

        // News Tawi (latest articles from 'Tawi' category)
        $newsTawi = Article::published()
            ->with(['author', 'category', 'tags'])
            ->whereHas('category', fn($q) => $q->where('name', 'Tawi'))
            ->whereNotIn('id', $shownArticleIds)
            ->orderByDesc('published_at')
            ->limit(5)
            ->get();
        $shownArticleIds->push(...$newsTawi->pluck('id'));

        // Latest (newest 5 articles, ignore shown list)
        $latest = Article::published()
            ->with(['author', 'category', 'tags'])
            ->orderByDesc('published_at')
            ->limit(5)
            ->get();

        // Final Response
        $response = [
            'Trending Now' => ArticleResource::collection($trending),
            'News nawi leh tawi' => ArticleResource::collection($newsNawi),
            'Recommended for You' => ArticleResource::collection($recommended),
            'Most Liked' => ArticleResource::collection($mostLiked),
            'From Authors You Read' => ArticleResource::collection($fromAuthors),
            'Editor\'s Picks' => ArticleResource::collection($editorsPicks),
            'Newly Published' => ArticleResource::collection($newlyPublished),
            'Latest' => ArticleResource::collection($latest),
        ];

        return response()->json(
            collect($response)->filter(fn($items) => $items->count() > 0)
        );
    }
}
