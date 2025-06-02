<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;

class ArticleTagController extends Controller
{
    // Attach tags to an article
    public function attach(Request $request, Article $article)
    {
        $data = $request->validate([
            'tags' => 'required|array',
            'tags.*' => 'exists:tags,id',
        ]);

        $article->tags()->syncWithoutDetaching($data['tags']);

        return response()->json([
            'message' => 'Tags attached successfully.',
            'tags' => $article->tags
        ]);
    }

    // Detach specific tags from an article
    public function detach(Request $request, Article $article)
    {
        $data = $request->validate([
            'tags' => 'required|array',
            'tags.*' => 'exists:tags,id',
        ]);

        $article->tags()->detach($data['tags']);

        return response()->json([
            'message' => 'Tags detached successfully.',
            'tags' => $article->tags
        ]);
    }
}
