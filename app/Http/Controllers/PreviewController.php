<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\VideoModel;
use App\Models\AudioModel;
use App\Models\User;

class PreviewController extends Controller
{
    public function show($type, $id)
    {
        $allowedTypes = ['watch', 'read', 'listen', 'view'];
        if (!in_array($type, $allowedTypes)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid type'
            ], 400);
        }

        // Select model based on type
        $model = match ($type) {
            'watch' => VideoModel::class,
            'read' => Article::class,
            'listen' => AudioModel::class,
            'view' => User::class,
        };

        $record = $model::find($id);

        if (!$record) {
            return response()->json([
                'status' => false,
                'message' => 'Content not found'
            ], 404);
        }

        // Customize data fields
        $title = $record->title ?? $record->name ?? 'Untitled';
        $description = $record->summary ?? $record->bio ?? '';

        // Set default thumbnail if empty or null
        $thumbnail = $record->cover_image_url ?? $record->profile_image_url ?? 'https://cdn.zostream.in/Normal/Vanneihtluanga/Vanneihtluanga%20coverpg.jpg';

        return response()->json([
            'status' => true,
            'title' => $title,
            'description' => $description,
            'thumbnail' => $thumbnail,
            'type' => $type,
            'id' => $id,
        ]);
    }
}
