<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    // List all users (admin only)
    public function index()
    {
        try {
            $users = User::paginate(15);

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
            $user = User::findOrFail($id);

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
            $data = $request->validate([
                'id' => 'required|string|max:100',
                'name' => 'required|string|max:100',
                'phone' => 'required|string|max:15',
                'email' => 'nullable|email|unique:users,email',
                'role' => ['required', Rule::in(['admin','editor','reader'])],
                'bio' => 'nullable|string',
                'profile_image_url' => 'nullable|url',
            ]);
            $user = User::create($data);

            return response()->json([
                'status' => true,
                'message' => 'User created successfully.',
                'data' => $user
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
                'message' => 'Failed to create user.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Update user
    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            $data = $request->validate([
                'name' => 'sometimes|string|max:100',
                'phone' => 'nullable|string|max:15',
                'email' => ['sometimes','email', Rule::unique('users')->ignore($user->id)],
                'role' => ['sometimes', Rule::in(['admin','editor','reader'])],
                'bio' => 'nullable|string',
                'profile_image_url' => 'nullable|url',
            ]);

            $user->update($data);

            return response()->json([
                'status' => true,
                'message' => 'User updated successfully.',
                'data' => $user
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
                'message' => 'Failed to update user.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Delete user
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
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
