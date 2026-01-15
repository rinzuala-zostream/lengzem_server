<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\videoModel;
use Http;
use Illuminate\Http\Request;

class VideoController extends Controller
{
    // List videos (optionally by status)
    public function index(Request $request)
    {
        try {
            $status = $request->query('status');
            $videos = $status
                ? videoModel::with('author')->where('status', $status)->paginate(10)
                : videoModel::with('author')->published()->paginate(10);

            return response()->json([
                'status' => true,
                'message' => 'Videos retrieved successfully.',
                'data' => $videos
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve videos.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Show a single video
    public function show(Request $request, $id)
    {
        try {
            $userId = $request->query('uid');
            $video = videoModel::published()->findOrFail($id);

            if ($video->is_premium) {
                if (!$userId) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Please log in to access premium content.',
                    ], 401);
                }

                $activeSubscription = Subscription::where('user_id', $userId)
                    ->where('status', 'active')
                    ->latest('id')
                    ->first();

                if (!$activeSubscription) {
                    return response()->json([
                        'status' => false,
                        'message' => 'He content en tur chuan subscription i neih a ngai, Lengzem i subscribe dawm em?.',
                    ], 403);
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Video retrieved successfully.',
                'data' => $video
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve video.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Create new video
   public function store(Request $request)
{
    try {
        // Normalize URLs (encode spaces)
        foreach (['url', 'thumbnail_url'] as $field) {
            if ($request->filled($field)) {
                $request->merge([
                    $field => str_replace(' ', '%20', trim($request->$field))
                ]);
            }
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'language' => 'nullable|string|max:50',
            'thumbnail_url' => 'nullable|url',
            'duration' => 'nullable|string', // HH:MM:SS
            'release_date' => 'nullable|date',
            'status' => 'required|in:draft,scheduled,published',
            'author_id' => 'required|exists:user,id',
            'is_premium' => 'nullable|boolean',
            'url' => 'required|url',
        ]);

        // Auto-extract duration if missing
        if (empty($validated['duration'])) {
            $validated['duration'] =
                $this->extractDurationFromMPD($validated['url']) ?? '00:00:00';
        }

        $video = videoModel::create($validated);

        return response()->json([
            'status' => true,
            'message' => 'Video created successfully.',
            'data' => $video
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
            'message' => 'Failed to create video.',
            'error' => $e->getMessage()
        ], 500);
    }
}

    private function extractDurationFromMPD($mpdUrl)
    {
        try {
            $response = Http::get(str_replace(" ", "%20", $mpdUrl));
            if ($response->ok()) {
                $xml = simplexml_load_string($response->body());
                $durationIso = (string) $xml['mediaPresentationDuration'];
                return $this->convertIso8601ToHms($durationIso);
            }
        } catch (\Exception $e) {
            // Silent fail, fallback handled in store()
        }

        return null;
    }

    private function convertIso8601ToHms($iso)
    {
        try {
            preg_match('/PT((\d+)H)?((\d+)M)?((\d+(\.\d+)?)S)?/', $iso, $m);
            $hours = isset($m[2]) ? (int) $m[2] : 0;
            $minutes = isset($m[4]) ? (int) $m[4] : 0;
            $seconds = isset($m[6]) ? (float) $m[6] : 0;

            $totalSeconds = ($hours * 3600) + ($minutes * 60) + $seconds;

            // Properly normalize into HH:MM:SS
            $hh = floor($totalSeconds / 3600);
            $mm = floor(($totalSeconds % 3600) / 60);
            $ss = floor($totalSeconds % 60);

            return sprintf('%02d:%02d:%02d', $hh, $mm, $ss);
        } catch (\Exception $e) {
            return '00:00:00';
        }
    }

    // Update video
    public function update(Request $request, $id)
    {
        try {
            $video = videoModel::findOrFail($id);

            $validated = $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'language' => 'nullable|string|max:50',
                'thumbnail_url' => 'nullable|url',
                'duration' => 'nullable',
                'release_date' => 'nullable|date',
                'status' => 'in:draft,scheduled,published',
                'author_id' => 'sometimes|required|exists:user,id', // Ensure author_id is valid if provided
                'is_premium' => 'nullable|boolean', // Optional field for premium content
                'url' => 'required|url', // Optional field for video URL
            ]);

            $video->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Video updated successfully.',
                'data' => $video
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
                'message' => 'Failed to update video.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Delete video
    public function destroy($id)
    {
        try {
            $video = videoModel::findOrFail($id);
            $video->delete();

            return response()->json([
                'status' => true,
                'message' => 'Video deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete video.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Change status
    public function updateStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|in:draft,scheduled,published'
            ]);

            $video = videoModel::findOrFail($id);
            $video->status = $request->input('status');
            $video->save();

            return response()->json([
                'status' => true,
                'message' => 'Video status updated successfully.',
                'data' => $video
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
                'message' => 'Failed to update video status.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
