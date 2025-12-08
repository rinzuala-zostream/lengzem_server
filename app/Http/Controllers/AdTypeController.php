<?php

namespace App\Http\Controllers;

use App\Models\AdType;
use Illuminate\Http\Request;

class AdTypeController extends Controller
{
    // ✅ Get all ad types
    public function index()
    {
        $types = AdType::all();

        return response()->json([
            'status' => true,
            'message' => 'Ad types fetched successfully.',
            'data' => $types
        ]);
    }

    // ✅ Get one ad type by ID
    public function show($id)
    {
        $type = AdType::find($id);

        if (!$type) {
            return response()->json([
                'status' => false,
                'message' => 'Ad type not found.'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Ad type found.',
            'data' => $type
        ]);
    }

    // ✅ Create a new ad type
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50|unique:ad_types,name'
        ]);

        $type = AdType::create([
            'name' => $request->name
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Ad type created successfully.',
            'data' => $type
        ], 201);
    }

    // ✅ Update an existing ad type
    public function update(Request $request, $id)
    {
        $type = AdType::find($id);

        if (!$type) {
            return response()->json([
                'status' => false,
                'message' => 'Ad type not found.'
            ], 404);
        }

        $request->validate([
            'name' => 'required|string|max:50|unique:ad_types,name,' . $id
        ]);

        $type->update(['name' => $request->name]);

        return response()->json([
            'status' => true,
            'message' => 'Ad type updated successfully.',
            'data' => $type
        ]);
    }

    // ✅ Delete an ad type
    public function destroy($id)
    {
        $type = AdType::find($id);

        if (!$type) {
            return response()->json([
                'status' => false,
                'message' => 'Ad type not found.'
            ], 404);
        }

        $type->delete();

        return response()->json([
            'status' => true,
            'message' => 'Ad type deleted successfully.'
        ]);
    }
}
