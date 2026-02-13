<?php

use App\Http\Controllers\AdController;
use App\Http\Controllers\AdTypeController;
use App\Http\Controllers\ArticleFeatures;
use App\Http\Controllers\ArticleReadTimeController;
use App\Http\Controllers\ArticleTagController;
use App\Http\Controllers\AudioController;
use App\Http\Controllers\CheckPendingPayment;
use App\Http\Controllers\CoverImageController;
use App\Http\Controllers\FCMNotificationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PreviewController;
use App\Http\Controllers\RazorpayController;
use App\Http\Controllers\RedeemCodeController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SubscriptionPlanController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\InteractionController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\UserDeleteController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\AdminUIController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\BannerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Authenticated user info routes
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//Route::middleware(['firebase.auth'])->group(function () {

Route::post('/article/add-time', [ArticleReadTimeController::class, 'store']);
Route::get('/article/{userId}/{articleId}/total-time', [ArticleReadTimeController::class, 'getTotal']);

//User routes
Route::get('/users/editors', [UserController::class, 'getEditors']);
Route::get('/users', [UserController::class, 'index']);
Route::get('/users/{id}', [UserController::class, 'show']);
Route::post('/users', [UserController::class, 'store']);
Route::put('/users/update/{id}', [UserController::class, 'update']);
Route::delete('/users/delete/{id}', [UserController::class, 'destroy']);

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
Route::get('/articles/search', [ArticleController::class, 'search']);
Route::get('/articles', [ArticleController::class, 'index']);

Route::middleware(['allow.origin'])->group(function () {
Route::get('/articles/{id}', [ArticleController::class, 'show']);
});


Route::get('/articles/admin/{id}', [ArticleController::class, 'adminShow']);
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

// 📽️ Video Routes
Route::get('/videos', [VideoController::class, 'index']);          // List all or by status
Route::post('/videos', [VideoController::class, 'store']);         // Create video
Route::get('/videos/{id}', [VideoController::class, 'show']);      // View single video (optional)
Route::put('/videos/{id}', [VideoController::class, 'update']);    // Update video
Route::delete('/videos/{id}', [VideoController::class, 'destroy']); // Delete video
Route::post('/videos/{id}/status', [VideoController::class, 'updateStatus']); // Update status

// 🎧 Audio Routes
Route::get('/audios', [AudioController::class, 'index']);          // List all or by status
Route::post('/audios', [AudioController::class, 'store']);         // Create audio
Route::get('/audios/{id}', [AudioController::class, 'show']);      // View single audio (optional)
Route::put('/audios/{id}', [AudioController::class, 'update']);    // Update audio
Route::delete('/audios/{id}', [AudioController::class, 'destroy']); // Delete audio
Route::post('/audios/{id}/status', [AudioController::class, 'updateStatus']); // Update status

//Payment routes
Route::get('/payments/check', [PaymentController::class, 'checkPaymentStatus']);
Route::get('/payments/razorpay/orders/{orderId}/status', [RazorpayController::class, 'checkPaymentStatus']);
Route::post('/payments/razorpay/orders', [RazorpayController::class, 'createOrder']);

//Ads routes
Route::get('/ads', [AdController::class, 'index']);
Route::get('/ads/{id}', [AdController::class, 'show']);
Route::post('/ads', [AdController::class, 'store']);
Route::delete('/ad-delete/{id}', [AdController::class, 'destroy']);

Route::get('/ad-types', [AdTypeController::class, 'index']);
Route::get('/ad-types/{id}', [AdTypeController::class, 'show']);
Route::post('/ad-types', [AdTypeController::class, 'store']);
Route::put('/ad-types/{id}', [AdTypeController::class, 'update']);
Route::delete('/ad-types/{id}', [AdTypeController::class, 'destroy']);

//});

Route::get('/content/{type}/{id}', [PreviewController::class, 'show']);

Route::delete('/account/delete', [UserDeleteController::class, 'deleteAccount']);

Route::post('/fcm/send', [FCMNotificationController::class, 'send']);

//Admin UI Data Route
Route::get('/admin/dashboard', [AdminUIController::class, 'index']);
Route::get('/admin/article-add-data', [AdminUIController::class, 'articleAddRes']);

// Banner Model Snippet from app/Models/Banner.php
Route::prefix('banners')->group(function () {
    Route::get('/', [BannerController::class, 'index']);
    Route::get('/search', [BannerController::class, 'search']);
    Route::get('/{id}', [BannerController::class, 'show']);
    Route::post('/', [BannerController::class, 'store']);
    Route::put('/{id}', [BannerController::class, 'update']);
    Route::delete('/{id}', [BannerController::class, 'destroy']);
});

//Redeem Code routes
Route::post('/redeem-codes', [RedeemCodeController::class, 'store']);  // Admin generate
Route::post('/redeem-codes/apply', [RedeemCodeController::class, 'apply']); // User apply

//Notification routes
Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications', [NotificationController::class, 'store']);

    Route::post('/notifications/article/{id}', [NotificationController::class, 'articleCreated']);
    Route::post('/notifications/subscription/{id}', [NotificationController::class, 'subscriptionCreated']);
    Route::post('/notifications/user/{id}', [NotificationController::class, 'userCreated']);

    Route::post('/notifications/{id}/approve', [NotificationController::class, 'approve']);
    Route::post('/notifications/{id}/reject', [NotificationController::class, 'reject']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);

//Cover Image Public Route
Route::prefix('cover-image')->group(function () {
    Route::get('/', [CoverImageController::class, 'index']);
    Route::post('/', [CoverImageController::class, 'store']);
    Route::put('{id}', [CoverImageController::class, 'update']);
    Route::delete('{id}', [CoverImageController::class, 'destroy']);
    Route::get('search', [CoverImageController::class, 'search']);
});

//this is for public routes, dont need auth middleware
Route::post('/article/public-post', [ArticleController::class, 'publicStore']);

//Article Feature routes
Route::get('/article-features', [ArticleFeatures::class, 'index']);
Route::post('/article-features', [ArticleFeatures::class, 'store']);
Route::get('/article-features/{id}', [ArticleFeatures::class, 'show']);
Route::put('/article-features/{id}', [ArticleFeatures::class, 'update']);
Route::delete('/article-features/{id}', [ArticleFeatures::class, 'destroy']);

