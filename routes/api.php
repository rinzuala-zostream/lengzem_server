<?php

use App\Http\Controllers\ArticleTagController;
use App\Http\Controllers\CheckPendingPayment;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SubscriptionPlanController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\InteractionController;
use App\Http\Controllers\MediaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Authenticated user info route
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware(['firebase.auth'])->group(function () {
    //User routes
    Route::get('/users/editors', [UserController::class, 'getEditors']);
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    

    //Category routes
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

    //Tag routes
    Route::get('/tags/search', [TagController::class, 'search']);
    Route::get('/tags', [TagController::class, 'index']);
    Route::get('/tags/{id}', [TagController::class, 'show']);
    Route::post('/tags', [TagController::class, 'store']);
    Route::put('/tags/{id}', [TagController::class, 'update']);
    Route::delete('/tags/{id}', [TagController::class, 'destroy']);

    //Article routes
    Route::get('/articles', [ArticleController::class, 'index']);
    Route::get('/articles/{id}', [ArticleController::class, 'show']);
    Route::post('/articles', [ArticleController::class, 'store']);
    Route::put('/articles/{id}', [ArticleController::class, 'update']);
    Route::delete('/articles/{id}', [ArticleController::class, 'destroy']);

    //Article tag routes
    Route::post('/articles/{article}/tags', [ArticleTagController::class, 'attach']);
    Route::delete('/articles/{article}/tags', [ArticleTagController::class, 'detach']);

    //Comment routes
    Route::get('/articles/{article_id}/comments', [CommentController::class, 'index']);
    Route::get('/articles/{article_id}/comments/{id}', [CommentController::class, 'show']);
    Route::post('/articles/{article_id}/comments', [CommentController::class, 'store']);
    Route::put('/articles/{article_id}/comments/{id}', [CommentController::class, 'update']);
    Route::delete('/articles/{article_id}/comments/{id}', [CommentController::class, 'destroy']);

    //Interraction routes
    Route::get('/articles/{article_id}/interactions', [InteractionController::class, 'index']);
    Route::get('/articles/{article_id}/interactions/{id}', [InteractionController::class, 'show']);
    Route::post('/articles/{article_id}/interactions', [InteractionController::class, 'store']);
    Route::delete('/articles/{article_id}/interactions/{id}', [InteractionController::class, 'destroy']);

    //Media routes
    Route::get('/articles/{article_id}/media', [MediaController::class, 'index']);
    Route::get('/articles/{article_id}/media/{id}', [MediaController::class, 'show']);
    Route::post('/articles/{article_id}/media', [MediaController::class, 'store']);
    Route::put('/articles/{article_id}/media/{id}', [MediaController::class, 'update']);
    Route::delete('/articles/{article_id}/media/{id}', [MediaController::class, 'destroy']);

    // Subscription Plan routes
    Route::get('/subscription-plans', [SubscriptionPlanController::class, 'index']);
    Route::post('/subscription-plans', [SubscriptionPlanController::class, 'store']);
    Route::get('/subscription-plans/{id}', [SubscriptionPlanController::class, 'show']);
    Route::put('/subscription-plans/{id}', [SubscriptionPlanController::class, 'update']);
    Route::delete('/subscription-plans/{id}', [SubscriptionPlanController::class, 'destroy']);

    // Subscription routes
    Route::get('/subscriptions', [SubscriptionController::class, 'index']);
    Route::post('/subscriptions', [SubscriptionController::class, 'store']);
    Route::get('/subscriptions/{id}', [SubscriptionController::class, 'show']);
    Route::put('/subscriptions/{id}', [SubscriptionController::class, 'update']);
    Route::delete('/subscriptions/{id}', [SubscriptionController::class, 'destroy']);
    Route::get('/subscriptions/{userId}/verify', [CheckPendingPayment::class, 'processUserPayments']);


    Route::get('/home', [HomeController::class, 'index']);

});

