<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Article;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * List notifications for logged-in user (admin/editor)
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!in_array($user->role, ['admin', 'editor'])) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $notifications = Notification::where('target_role', $user->role)
            ->when($request->status, fn ($q) =>
                $q->where('status', $request->status)
            )
            ->when($request->unread, fn ($q) =>
                $q->where('is_read', false)
            )
            ->latest()
            ->with(['actor', 'notifiable'])
            ->paginate(15);

        return response()->json($notifications);
    }

    /**
     * Store notification manually (generic)
     */
    public function store(Request $request)
    {
        $request->validate([
            'notifiable_type' => 'required|string',
            'notifiable_id'   => 'required|integer',
            'action'          => 'required|string',
            'message'         => 'nullable|string',
            'target_role'     => 'required|in:admin,editor',
        ]);

        $notification = Notification::create([
            'notifiable_type' => $request->notifiable_type,
            'notifiable_id'   => $request->notifiable_id,
            'actor_id'        => Auth::id(),
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
     * Create notification for contributor article
     */
    public function articleCreated($articleId)
    {
        $article = Article::findOrFail($articleId);

        if (!$article->isContributor || $article->isApproved) {
            return response()->json([
                'message' => 'Notification not required'
            ]);
        }

        $notification = Notification::create([
            'notifiable_type' => Article::class,
            'notifiable_id'   => $article->id,
            'actor_id'        => $article->user_id ?? null,
            'action'          => 'article_created',
            'message'         => 'New contributor article pending approval',
            'target_role'     => 'admin',
        ]);

        return response()->json($notification, 201);
    }

    /**
     * Create notification for new subscription
     */
    public function subscriptionCreated($subscriptionId)
    {
        $subscription = Subscription::findOrFail($subscriptionId);

        $notification = Notification::create([
            'notifiable_type' => Subscription::class,
            'notifiable_id'   => $subscription->id,
            'actor_id'        => $subscription->user_id ?? null,
            'action'          => 'subscription_created',
            'message'         => 'New subscription created',
            'target_role'     => 'admin',
        ]);

        return response()->json($notification, 201);
    }

    /**
     * Create notification for admin/editor user approval
     */
    public function userCreated($userId)
    {
        $user = User::findOrFail($userId);

        if (!in_array($user->role, ['admin', 'editor']) || $user->isApproved) {
            return response()->json([
                'message' => 'Notification not required'
            ]);
        }

        $notification = Notification::create([
            'notifiable_type' => User::class,
            'notifiable_id'   => $user->id,
            'actor_id'        => null,
            'action'          => 'user_created',
            'message'         => 'New admin/editor account pending approval',
            'target_role'     => 'admin',
        ]);

        return response()->json($notification, 201);
    }

    /**
     * Approve notification
     */
    public function approve($id)
    {
        $notification = Notification::with('notifiable')->findOrFail($id);

        if (Auth::user()->role !== 'admin') {
            return response()->json([
                'message' => 'Only admin can approve'
            ], 403);
        }

        switch ($notification->notifiable_type) {
            case Article::class:
                $notification->notifiable->update([
                    'isApproved' => true
                ]);
                break;

            case User::class:
                $notification->notifiable->update([
                    'isApproved' => true
                ]);
                break;
        }

        $notification->update([
            'status'  => 'approved',
            'is_read' => true
        ]);

        return response()->json([
            'message' => 'Approved successfully'
        ]);
    }

    /**
     * Reject notification
     */
    public function reject($id)
    {
        $notification = Notification::with('notifiable')->findOrFail($id);

        if (Auth::user()->role !== 'admin') {
            return response()->json([
                'message' => 'Only admin can reject'
            ], 403);
        }

        $notification->update([
            'status'  => 'rejected',
            'is_read' => true
        ]);

        return response()->json([
            'message' => 'Rejected successfully'
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($id)
    {
        $notification = Notification::findOrFail($id);

        $notification->update([
            'is_read' => true
        ]);

        return response()->json([
            'message' => 'Marked as read'
        ]);
    }
}
