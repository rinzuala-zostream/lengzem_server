<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\AudioModel;
use App\Models\User;
use App\Models\Video;

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
            'watch' => Video::class,
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
        if ($type === 'listen') {
            // For Audio, check if it has a thumbnail, otherwise fallback to default image
            $thumbnail = $record->thumbnail ?? 'https://cdn.zostream.in/Normal/Vanneihtluanga/Vanneihtluanga%20coverpg.jpg';
        } elseif ($type === 'watch') {
            // For Video, check if it has a thumbnail, otherwise fallback to default image
            $thumbnail = $record->thumbnail ?? 'https://cdn.zostream.in/Normal/Vanneihtluanga/Vanneihtluanga%20coverpg.jpg';
        } else {
            // Default for other types (e.g., Article or User)
            $thumbnail = $record->cover_image_url ?? 'https://cdn.zostream.in/Normal/Vanneihtluanga/Vanneihtluanga%20coverpg.jpg';
        }

        // Check for the author and append it to the title
        $author = null;
        if (method_exists($record, 'author')) {
            $author = $record->author()->first();  // Assuming 'author' is a relationship method
        }

        if ($author) {
            $title .= ' (by ' . $author->name . ')';
        }

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
