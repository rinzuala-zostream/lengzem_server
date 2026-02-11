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
                ->with('isAproved', true)
                ->orderByDesc('view_count')
                ->limit(20)
                ->get();
        });
        $shownArticleIds->push(...$trending->pluck('id'));

        // Editor's Picks
        $editorsPicks = Cache::remember('home_editors_picks', 60, function () use ($shownArticleIds) {
            return Article::published()
                ->with(['author', 'category', 'tags'])
                ->with('isAproved', true)
                ->where('view_count', '>', 1000)
                ->whereNotIn('id', $shownArticleIds)
                ->orderByDesc('view_count')
                ->limit(20)
                ->get();
        });
        $shownArticleIds->push(...$editorsPicks->pluck('id'));

        // Newly Published
        $newlyPublished = Article::published()
            ->with(['author', 'category', 'tags'])
            ->with('isAproved', true)
            ->orderByDesc('published_at')
            ->limit(20)
            ->get();

        /*$newlyPublished = ArticleFeatureModel::orderByRaw("STR_TO_DATE(month_year, '%Y-%m') DESC")
            ->paginate(12);*/

        $shownArticleIds->push(...$newlyPublished->pluck('id'));

        // Most Liked
        $mostLiked = Cache::remember('home_most_liked', 60, function () {
            return Article::published()
                ->with(['author', 'category', 'tags'])
                ->with('isAproved', true)
                ->withCount([
                    'interactions as like_count' => fn($query) => $query->where('type', 'like')
                ])
                ->orderByDesc('like_count')
                ->limit(20)
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
                    ->with('isAproved', true)
                    ->whereHas('tags', fn($q) => $q->whereIn('tags.id', $likedTagIds))
                    ->whereNotIn('id', $shownArticleIds)
                    ->orderByDesc('published_at')
                    ->limit(20)
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
                    ->with('isAproved', true)
                    ->whereIn('author_id', $authorIds)
                    ->whereNotIn('id', $shownArticleIds)
                    ->orderByDesc('published_at')
                    ->limit(20)
                    ->get();
                $shownArticleIds->push(...$fromAuthors->pluck('id'));
            }
        }

        // News Nawi (latest articles from 'Nawi' category)
        $newsNawi = Article::published()
            ->with(['author', 'category', 'tags'])
            ->whereHas('category', fn($q) => $q->where('name', 'News nawi leh tawi'))
            ->with('isAproved', true)
            ->whereNotIn('id', $shownArticleIds)
            ->orderByDesc('published_at')
            ->limit(20)
            ->get();
        $shownArticleIds->push(...$newsNawi->pluck('id'));

        // Final Response
        $response = [
            'Newly Published' => ArticleResource::collection($newlyPublished),
            'Trending Now' => ArticleResource::collection($trending),
            'News nawi leh tawi' => ArticleResource::collection($newsNawi),
            'Recommended for You' => ArticleResource::collection($recommended),
            'Most Liked' => ArticleResource::collection($mostLiked),
            'From Authors You Read' => ArticleResource::collection($fromAuthors),
            'Editor\'s Picks' => ArticleResource::collection($editorsPicks),

        ];

        return response()->json(
            collect($response)->filter(fn($items) => $items->count() > 0)
        );
    }
}
