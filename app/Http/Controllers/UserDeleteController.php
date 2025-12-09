<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\Models\User;
use Kreait\Firebase\Factory;
use Exception;

class UserDeleteController extends Controller
{
    protected $auth;

    public function __construct()
    {
        // ✅ Load Firebase service account from root firebase.json
        $this->auth = (new Factory)
            ->withServiceAccount(base_path('firebase.json'))
            ->createAuth();
    }

    /**
     * Securely delete the authenticated user's account
     */
    public function deleteAccount(Request $request)
    {
        $idToken = $request->bearerToken();

        if (!$idToken) {
            return response()->json([
                'status' => false,
                'message' => 'Missing Authorization Bearer token',
            ], 401);
        }

        try {
            // 🔹 Verify the Firebase ID token
            $verifiedIdToken = $this->auth->verifyIdToken($idToken);
            $uid = $verifiedIdToken->claims()->get('sub'); // Firebase UID

            // 🔹 Delete user from Firebase Authentication
            $this->auth->deleteUser($uid);

            // 🔹 Delete user from local database if exists
            User::where('firebase_uid', $uid)->delete();

            return response()->json([
                'status' => true,
                'message' => 'Your account has been deleted successfully',
            ], 200);

        } catch (\Kreait\Firebase\Exception\Auth\FailedToVerifyToken $e) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid or expired token',
            ], 401);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete account: ' . $e->getMessage(),
            ], 500);
        }
    }
}