<?php

namespace App\Http\Controllers;

use App\Models\Interaction;
use Illuminate\Http\Request;

class InteractionController extends Controller
{
    public function index($articleId)
    {
        try {
            $interactions = Interaction::where('article_id', $articleId)->get();

            return response()->json([
                'status' => true,
                'message' => 'Interactions retrieved successfully.',
                'data' => $interactions,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve interactions.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request, $articleId)
    {
        try {
            $data = $request->validate([
                'user_id' => 'required|exists:user,id',
                'type' => 'required|in:like,dislike,bookmark',
            ]);

            $data['article_id'] = $articleId;

            // Prevent duplicate interaction types for same user and article
            $existing = Interaction::where('article_id', $articleId)
                ->where('user_id', $data['user_id'])
                ->where('type', $data['type'])
                ->first();

            if ($existing) {
                return response()->json([
                    'status' => false,
                    'message' => 'Interaction already exists',
                ], 409);
            }

            $interaction = Interaction::create($data);

            return response()->json([
                'status' => true,
                'message' => 'Interaction created successfully.',
                'data' => $interaction,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create interaction.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($articleId, $id)
    {
        try {
            $interaction = Interaction::where('id', $id)
                ->where('article_id', $articleId)
                ->first();

            if (!$interaction) {
                return response()->json([
                    'status' => false,
                    'message' => 'Interaction not found for this article.',
                ], 404);
            }

            $interaction->delete();

            return response()->json([
                'status' => true,
                'message' => 'Interaction deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete interaction.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
