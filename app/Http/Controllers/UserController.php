<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Controllers\AuthorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{

    // List all users (admin only)
    public function index()
    {
        try {
            $users = User::withCount('articles')->paginate(15);

            return response()->json([
                'status' => true,
                'message' => 'Users retrieved successfully.',
                'data' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve users.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Show a single user
    public function show($id)
    {
        try {
            $user = User::withCount('articles')
                ->where('id', $id)->firstOrFail();

            return response()->json([
                'status' => true,
                'message' => 'User retrieved successfully.',
                'data' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve user.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Create new user
    public function store(Request $request)
    {
        try {
            // Validate the incoming request
            $data = $request->validate([
                'id' => 'nullable|string|max:100',
                'name' => 'nullable|string|max:100',
                'phone' => 'nullable|string|max:15',
                'email' => 'nullable|email', // Allow email without uniqueness check
                'role' => ['nullable', Rule::in(['admin', 'editor', 'reader'])],
                'bio' => 'nullable|string',
                'profile_image_url' => 'nullable|url',
                'token' => 'nullable|string',
            ]);

            // Check if the email already exists within the same user
            if ($data['email']) {
                $existingUser = User::where('email', $data['email'])->where('id', '!=', $data['id'])->first();
                if ($existingUser) {
                    return response()->json([
                        'status' => false,
                        'message' => 'This email is already associated with another user.',
                    ]);
                }
            }

            // Create or update the user
            $user = User::updateOrCreate(['id' => $data['id']], $data);

            return response()->json([
                'status' => true,
                'message' => 'User created successfully.',
                'data' => $user
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'error' => $e->errors()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create user.',
                'error' => $e->getMessage()
            ]);
        }
    }

    // Update user
    public function update(Request $request, $id)
    {
        try {
            $user = User::where('id', $id)->first();

            $data = $request->validate([
                'name' => 'nullable|string|max:100',
                'phone' => 'nullable|string|max:15',
                'email' => 'nullable|email',
                'role' => ['nullable', Rule::in(['admin', 'editor', 'reader'])],
                'bio' => 'nullable|string',
                'profile_image_url' => 'nullable|url',
                'token' => 'nullable|string',
            ]);

            $user->updateOrCreate($data);

            return response()->json([
                'status' => true,
                'message' => 'User updated successfully.',
                'data' => $user
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
                'message' => 'Failed to update user.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Get all users with role 'editor'
    public function getEditors()
    {
        try {
            $editors = User::withCount('articles')
                ->where('role', 'editor')
                ->paginate(100);

            return response()->json([
                'status' => true,
                'message' => 'Editors retrieved successfully.',
                'data' => $editors
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve editors.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Delete user
    public function destroy($id)
    {
        try {
            $user = User::where('id', $id)->firstOrFail();
            $user->delete();

            return response()->json([
                'status' => true,
                'message' => 'User deleted successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete user.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
