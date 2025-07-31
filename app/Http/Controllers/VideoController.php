<?php

namespace App\Http\Controllers;

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
    public function show($id)
    {
        try {
            $video = videoModel::published()->findOrFail($id);

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
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'language' => 'nullable|string|max:50',
                'thumbnail_url' => 'nullable|url',
                'duration' => 'nullable',
                'release_date' => 'nullable|date',
                'status' => 'required|in:draft,scheduled,published',
                'author_id' => 'required|exists:user,id',
                'is_premium' => 'nullable|boolean',
                'url' => 'required|url',
            ]);

            // If duration is empty, extract from MPD
            if (empty($validated['duration'])) {
                try {
                    $response = Http::get($validated['url']);
                    if ($response->ok()) {
                        $xml = simplexml_load_string($response->body());
                        $xml->registerXPathNamespace('mpd', 'urn:mpeg:dash:schema:mpd:2011');
                        $durations = $xml->xpath('//mpd:MPD/@mediaPresentationDuration');

                        if (!empty($durations)) {
                            $durationIso = (string) $durations[0];
                            $validated['duration'] = self::convertIso8601ToHms($durationIso);
                        }
                    }
                } catch (\Exception $e) {
                    // Fail silently, duration will remain null
                }
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

    private static function convertIso8601ToHms($isoDuration)
    {
        try {
            $interval = new \DateInterval($isoDuration);
            $hours = $interval->h + ($interval->d * 24);
            $minutes = $interval->i;
            $seconds = $interval->s;

            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        } catch (\Exception $e) {
            return null; // return null on failure
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
