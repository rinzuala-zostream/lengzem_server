<?php

namespace App\Http\Controllers;

use App\Models\Interaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Comment;
use App\Models\Video;
use App\Models\AudioModel;
use App\Models\Article;
use App\Models\Category;
use App\Http\Resources\ArticleResource;
use App\Http\Resources\CategoryResource;

class AdminUIController extends Controller
{
    public function index()
    {
        $users = User::count();
        $subscriptions = Subscription::count();
        $comments = Comment::count();
        $videos = Video::count();
        $audios = AudioModel::count();
        $articles = Article::count();
        $categories = Category::count();

        return response()->json([
            'status' => true,
            'data' => [
                'users' => $users,
                'subscriptions' => $subscriptions,
                'comments' => $comments,
                'videos' => $videos,
                'audios' => $audios,
                'articles' => $articles,
                'categories' => $categories
            ]
        ]);
    }
}