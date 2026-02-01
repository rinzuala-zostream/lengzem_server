<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Notification;
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
        $data = $request->validate([
            'id' => 'required|string|max:100',
            'name' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:15',
            'email' => 'nullable|email',
            'role' => ['nullable', Rule::in(['admin', 'editor', 'reader'])],
            'bio' => 'nullable|string',
            'address' => ['nullable', 'string', 'max:50'],
            'dob' => ['nullable', 'date'],
            'profile_image_url' => 'nullable|url',
            'isApproved' => 'nullable|boolean',
            'token' => 'nullable|string',
        ]);

        // Email uniqueness check
        if (!empty($data['email'])) {
            $existingUser = User::where('email', $data['email'])
                ->where('id', '!=', $data['id'])
                ->first();

            if ($existingUser) {
                return response()->json([
                    'status' => false,
                    'message' => 'This email is already associated with another user.',
                ]);
            }
        }

        // Detect create vs update
        $isNewUser = !User::where('id', $data['id'])->exists();

        // Force approval false for admin/editor
        if (in_array($data['role'] ?? null, ['admin', 'editor'])) {
            $data['isApproved'] = false;
        }

        // Create or update user
        $user = User::updateOrCreate(
            ['id' => $data['id']],
            $data
        );

        /**
         * 🔔 Create notification ONLY IF:
         * - New user
         * - Role is admin/editor
         * - Not approved
         */
        if (
            $isNewUser &&
            in_array($user->role, ['admin', 'editor']) &&
            !$user->isApproved
        ) {
            Notification::create([
                'notifiable_type' => User::class,
                'notifiable_id'   => $user->id,
                'actor_id'        => auth()->id(), // who created this user (if logged in)
                'action'          => 'user_created',
                'message'         => "New {$user->role} account pending approval",
                'target_role'     => 'admin',
                'status'          => 'pending',
            ]);
        }

        return response()->json([
            'status'  => true,
            'message' => 'User created successfully.',
            'data'    => $user
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
            $user = User::findOrFail($id);

            $data = $request->validate([
                'name' => ['nullable', 'string', 'max:100'],
                'phone' => ['nullable', 'string', 'max:15'],
                'email' => [
                    'nullable',
                    'email',
                    Rule::unique('users', 'email')->ignore($id) // adjust table/column if needed
                ],
                'role' => ['nullable', Rule::in(['admin', 'editor', 'reader'])],
                'bio' => ['nullable', 'string'],
                'address' => ['nullable', 'string', 'max:50'],
                'dob' => ['nullable', 'date'], 
                'profile_image_url' => ['nullable', 'url'],
                'isApproved' => ['nullable', 'boolean'],
                'token' => ['nullable', 'string'],
            ]);

            // If you don’t want to null-out absent fields, remove nulls:
            // $data = array_filter($data, fn($v) => !is_null($v));

            $user->fill($data)->save(); // or $user->update($data);

            return response()->json([
                'status' => true,
                'message' => 'User updated successfully.',
                'data' => $user->fresh(),
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
    // Get all users with role 'editor' or 'admin'
public function getEditors()
{
    try {
        $editors = User::withCount('articles')
            ->whereIn('role', ['editor', 'admin'])->where('isApproved', true)
            ->orderBy('role') // Optional: order by role first
            ->orderBy('created_at', 'desc') // Optional: order by creation date
            ->paginate(100);

        return response()->json([
            'status' => true,
            'message' => 'Editors and Admins retrieved successfully.',
            'data' => $editors
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Failed to retrieve editors and admins.',
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
