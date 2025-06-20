<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    // Get paginated comments for a specific article
    public function index($articleId)
    {
        try {
            $comments = Comment::where('article_id', $articleId)
                ->whereNull('parent_id')
                ->with('user')          // load the user who posted the comment
                ->withCount('replies') // only get the count of replies, no replies data
                ->orderByDesc('created_at')
                ->paginate(10);

            return response()->json([
                'status' => true,
                'message' => 'Comments retrieved successfully.',
                'data' => $comments
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to load comments.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Store a new comment
    public function store(Request $request, $articleId)
    {
        try {
            $data = $request->validate([
                'user_id' => 'required|exists:user,id',
                'comment' => 'required|string',
                'parent_id' => 'nullable|exists:comments,id',
            ]);

            $data['article_id'] = $articleId;

            $comment = Comment::create($data);

            return response()->json([
                'status' => true,
                'message' => 'Comment added successfully.',
                'data' => $comment->load('user')
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while saving the comment.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Show a single comment with its replies and user info
    public function show(Request $request, $articleId, $id)
    {
        try {
            $comment = Comment::where('article_id', $articleId)
                ->where('id', $id)
                ->with('user')
                ->firstOrFail();

            // Get direct replies with their user and count of their own replies
            $replies = Comment::where('parent_id', $id)
                ->with('user')             // Load user
                ->withCount('replies')     // Only count nested replies
                ->orderBy('created_at')
                ->paginate(5);

            return response()->json([
                'status' => true,
                'message' => 'Comment retrieved successfully.',
                'data' => [
                    'comment' => $comment,
                    'replies' => $replies
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve comment.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Update a comment
    public function update(Request $request, $articleId, $id)
    {
        try {
            $comment = Comment::where('article_id', $articleId)->findOrFail($id);

            $data = $request->validate([
                'comment' => 'required|string',
            ]);

            $comment->update($data);

            return response()->json([
                'status' => true,
                'message' => 'Comment updated successfully.',
                'data' => $comment
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update comment.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Delete a comment
    public function destroy($articleId, $id)
    {
        try {
            // Find the parent comment
            $comment = Comment::where('article_id', $articleId)->findOrFail($id);

            // Delete all replies associated with this comment (if any)
            $comment->replies()->delete();

            // Delete the parent comment
            $comment->delete();

            return response()->json([
                'status' => true,
                'message' => 'Comment and its replies deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete comment.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
