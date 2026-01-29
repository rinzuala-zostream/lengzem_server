<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Article;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * List notifications based on user role
     * GET /notifications?user_id=1
     */
    public function index(Request $request)
{
    $request->validate([
        'role' => 'required|string|in:admin,editor,reader'
    ]);

    $role = $request->role;

    if (!in_array($role, ['admin', 'editor'])) {
        return response()->json([
            'data' => [],
            'message' => 'No notifications for this role'
        ]);
    }

    $notifications = Notification::query()
        ->where('target_role', $role)
        ->when($request->status, fn ($q) => $q->where('status', $request->status))
        ->when($request->boolean('unread'), fn ($q) => $q->where('is_read', false))
        ->latest()
        ->with(['actor', 'notifiable'])
        ->paginate(15);

    return response()->json($notifications);
}

    /**
     * Store notification manually
     */
    public function store(Request $request)
    {
        $request->validate([
            'notifiable_type' => 'required|string',
            'notifiable_id'   => 'required',
            'action'          => 'required|string',
            'message'         => 'nullable|string',
            'target_role'     => 'required|in:admin,editor',
            'actor_id'        => 'nullable'
        ]);

        $notification = Notification::create([
            'notifiable_type' => $request->notifiable_type,
            'notifiable_id'   => $request->notifiable_id,
            'actor_id'        => $request->actor_id,
            'action'          => $request->action,
            'message'         => $request->message,
            'target_role'     => $request->target_role,
        ]);

        return response()->json([
            'message' => 'Notification created',
            'data'    => $notification
        ], 201);
    }

    /**
     * Contributor article created → admin approval
     */
    public function articleCreated($articleId)
    {
        $article = Article::findOrFail($articleId);

        if (!$article->isContributor || $article->isApproved) {
            return response()->json(['message' => 'Notification not required']);
        }

        Notification::create([
            'notifiable_type' => Article::class,
            'notifiable_id'   => $article->id,
            'actor_id'        => $article->user_id,
            'action'          => 'article_created',
            'message'         => 'New contributor article pending approval',
            'target_role'     => 'admin',
        ]);

        return response()->json(['message' => 'Notification created'], 201);
    }

    /**
     * New subscription
     */
    public function subscriptionCreated($subscriptionId)
    {
        $subscription = Subscription::findOrFail($subscriptionId);

        Notification::create([
            'notifiable_type' => Subscription::class,
            'notifiable_id'   => $subscription->id,
            'actor_id'        => $subscription->user_id,
            'action'          => 'subscription_created',
            'message'         => 'New subscription created',
            'target_role'     => 'admin',
        ]);

        return response()->json(['message' => 'Notification created'], 201);
    }

    /**
     * Admin/editor user created → admin approval
     */
    public function userCreated($userId)
    {
        $user = User::findOrFail($userId);

        if (!in_array($user->role, ['admin', 'editor']) || $user->isApproved) {
            return response()->json(['message' => 'Notification not required']);
        }

        Notification::create([
            'notifiable_type' => User::class,
            'notifiable_id'   => $user->id,
            'actor_id'        => null,
            'action'          => 'user_created',
            'message'         => 'New admin/editor account pending approval',
            'target_role'     => 'admin',
        ]);

        return response()->json(['message' => 'Notification created'], 201);
    }

    /**
     * Approve (admin only)
     */
    public function approve(Request $request, $id)
    {

        $notification = Notification::with('notifiable')->findOrFail($id);

        if (in_array($notification->notifiable_type, [Article::class, User::class])) {
            $notification->notifiable->update(['isApproved' => true]);
        }

        $notification->update([
            'status'  => 'approved',
            'is_read' => true
        ]);

        return response()->json(['message' => 'Approved successfully']);
    }

    /**
     * Reject (admin only)
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'user_id' => 'required'
        ]);

        $admin = User::findOrFail($request->user_id);

        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'Only admin can reject'], 403);
        }

        Notification::findOrFail($id)->update([
            'status'  => 'rejected',
            'is_read' => true
        ]);

        return response()->json(['message' => 'Rejected successfully']);
    }

    /**
     * Mark as read
     */
    public function markAsRead($id)
    {
        Notification::findOrFail($id)->update(['is_read' => true]);

        return response()->json(['message' => 'Marked as read']);
    }
}
