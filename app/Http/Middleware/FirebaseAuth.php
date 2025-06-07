<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Kreait\Firebase\Factory;

class FirebaseAuth
{
    protected $auth;

    public function __construct()
    {
        $this->auth = (new Factory)
            ->withServiceAccount(base_path('firebase.json'))
            ->createAuth();
    }

    public function handle(Request $request, Closure $next)
    {
        $idToken = $request->bearerToken();

        if (!$idToken) {
            return response()->json([
                'status' => false,
                'message' => 'Authorization token is missing. Please login again.',
                'code' => 'AUTH_TOKEN_MISSING'
            ], 401);
        }

        try {
            $verifiedIdToken = $this->auth->verifyIdToken($idToken);
            $firebaseUid = $verifiedIdToken->claims()->get('sub');

            // Attach UID to request for downstream use
            $request->merge(['firebase_uid' => $firebaseUid]);

            return $next($request);
        } catch (\Kreait\Firebase\Exception\Auth\FailedToVerifyToken $e) {
            return response()->json([
                'status' => false,
                'message' => 'Your session has expired or the token is invalid. Please re-authenticate.',
                'code' => 'INVALID_FIREBASE_TOKEN',
                'details' => $e->getMessage()
            ], 401);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Authentication failed due to an unexpected error.',
                'code' => 'FIREBASE_AUTH_ERROR',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
