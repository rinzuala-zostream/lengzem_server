<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\AdMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdController extends Controller
{
    public function index()
    {
        $ads = Ad::with(['type', 'media'])
                 ->where('status', 'active')
                 ->get();

        return response()->json([
            'status' => true,
            'message' => 'Active ads fetched successfully.',
            'data' => $ads
        ]);
    }

    public function show($id)
    {
        try {
            $ad = Ad::with(['type', 'media'])->findOrFail($id);

            return response()->json([
                'status' => true,
                'message' => 'Ad found.',
                'data' => $ad
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Ad not found.',
                'data' => null
            ], 404);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:100',
            'description' => 'nullable|string',
            'type_id' => 'required|exists:ad_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'in:active,inactive,expired',
            'media' => 'array',
            'media.*.url' => 'required|url',
            'media.*.type' => 'required|in:image,video',
        ]);

        try {
            $ad = Ad::create($request->only([
                'title', 'description', 'type_id', 'start_date', 'end_date', 'status'
            ]));

            foreach ($request->media ?? [] as $media) {
                AdMedia::create([
                    'ad_id' => $ad->id,
                    'media_url' => $media['url'],
                    'media_type' => $media['type'],
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => 'Ad created successfully.',
                'data' => $ad->load('media', 'type')
            ], 201);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => 'Failed to create ad.',
                'data' => null,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
