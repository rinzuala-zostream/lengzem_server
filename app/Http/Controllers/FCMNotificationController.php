<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;

class FCMNotificationController extends Controller
{
    public function send(Request $request)
    {
        $title = $request->input('title', '');
        $body = $request->input('body', '');
        $image = $request->input('image', '');
        $key = $request->input('key');

        // Load credentials from JSON file
        $jsonPath = storage_path('app/firebase/lengzem.json');
        if (!file_exists($jsonPath)) {
            return response()->json(['error' => 'Firebase credentials not found'], 500);
        }

        $serviceAccountData = json_decode(file_get_contents($jsonPath), true);
        if (!$serviceAccountData) {
            return response()->json(['error' => 'Invalid Firebase credential format'], 500);
        }

        $accessToken = $this->getAccessToken($serviceAccountData);
        $response = $this->sendNotification($accessToken, $title, $body, $image, $key);

        return response()->json($response);
    }

    private function getAccessToken($serviceAccountData)
    {
        $client = new Client();
        $url = "https://oauth2.googleapis.com/token";

        $now = time();
        $expires = $now + 3600;

        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT'
        ];

        $claimSet = [
            'iss' => $serviceAccountData['client_email'],
            'scope' => 'https://www.googleapis.com/auth/cloud-platform https://www.googleapis.com/auth/firebase.messaging',
            'aud' => $serviceAccountData['token_uri'],
            'iat' => $now,
            'exp' => $expires
        ];

        $jwt = $this->base64UrlEncode(json_encode($header)) . '.' . $this->base64UrlEncode(json_encode($claimSet));
        $privateKey = openssl_pkey_get_private($serviceAccountData['private_key']);
        openssl_sign($jwt, $signature, $privateKey, 'sha256');
        $jwt .= '.' . $this->base64UrlEncode($signature);

        $response = $client->post($url, [
            'form_params' => [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt
            ]
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        return $data['access_token'];
    }

    private function sendNotification($accessToken, $title, $body, $image, $key = null)
    {
        $client = new Client();
        $url = 'https://fcm.googleapis.com/v1/projects/lengzem-app-bd7eb/messages:send';

        $message = [
            "message" => [
                "topic" => "all",
                "notification" => [
                    "title" => $title,
                    "body" => $body,
                    "image" => $image
                ]
            ]
        ];

        if ($key !== null) {
            $message['message']['data'] = ["key" => $key];
        }

        $response = $client->post($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($message)
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    private function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
