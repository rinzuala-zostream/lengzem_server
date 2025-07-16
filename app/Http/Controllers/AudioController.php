<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AudioModel;

class AudioController extends Controller
{
    public function index(Request $request)
    {
        try {
            $status = $request->query('status');
            $audios = $status
                ? AudioModel::published()->where('status', $status)->get()
                : AudioModel::published()->get();

            return response()->json([
                'status' => true,
                'message' => 'Audios retrieved successfully.',
                'data' => $audios
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve audios.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $audio = AudioModel::published()->findOrFail($id);
            return response()->json([
                'status' => true,
                'message' => 'Audio retrieved successfully.',
                'data' => $audio
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve audio.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'language' => 'nullable|string|max:50',
                'thumbnail_url' => 'nullable|url',
                'duration' => 'nullable',
                'release_date' => 'nullable|date',
                'status' => 'required|in:draft,scheduled,published',
            ]);

            $audio = AudioModel::create($data);

            return response()->json([
                'status' => true,
                'message' => 'Audio created successfully.',
                'data' => $audio
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'error' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create audio.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $audio = AudioModel::findOrFail($id);

            $data = $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'language' => 'nullable|string|max:50',
                'thumbnail_url' => 'nullable|url',
                'duration' => 'nullable',
                'release_date' => 'nullable|date',
                'status' => 'in:draft,scheduled,published',
            ]);

            $audio->update($data);

            return response()->json([
                'status' => true,
                'message' => 'Audio updated successfully.',
                'data' => $audio
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'error' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update audio.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $audio = AudioModel::findOrFail($id);
            $audio->delete();

            return response()->json([
                'status' => true,
                'message' => 'Audio deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete audio.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|in:draft,scheduled,published'
            ]);

            $audio = AudioModel::findOrFail($id);
            $audio->status = $request->status;
            $audio->save();

            return response()->json([
                'status' => true,
                'message' => 'Audio status updated successfully.',
                'data' => $audio
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'error' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update audio status.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
