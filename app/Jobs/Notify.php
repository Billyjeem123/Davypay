<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\HttpHandler\HttpHandlerFactory;

class Notify
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user_id;
    public $title;
    public $body;
    public $icon;
    public $action;
    public $data;

    /**
     * Create a new job instance.
     */
    public function __construct($user_id, $title, $body, $icon = null, $action = null, $data = null)
    {
        $this->user_id = $user_id;
        $this->title = $title;
        $this->body = $body;
        $this->action = empty($action) ? 'https://api.paypointapp.africa' : $action;
        $this->data = empty($data) ? [] : $data;
        $this->icon = empty($icon) ? asset('assets/images/application/logo.jpg') : $icon;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $user = User::where('id', $this->user_id)->first();

        if (!$user || !isset($user->device_token) || empty($user->device_token)) {
            \Log::info('No valid device token found for user: ' . $this->user_id);
            return;
        }

        // Try legacy FCM API first
        $success = $this->sendLegacyFCM($user);

        // If legacy fails, try FCM v1 API
        if (!$success) {
            \Log::info('Legacy FCM failed, trying FCM v1 API');
            $this->sendFCMv1($user);
        }
    }

    /**
     * Send using Legacy FCM API
     */
    private function sendLegacyFCM($user): bool
    {
        $serverKey = config('services.firebase.server_key');

        if (empty($serverKey)) {
            \Log::error('FCM server key not configured');
            return false;
        }

        $data = [
            "to" => $user->device_token,
            "notification" => [
                "title" => $this->title,
                "body" => $this->body,
                "icon" => $this->icon,
                "click_action" => $this->action
            ],
            "data" => $this->data,
            "priority" => "high"
        ];

        $headers = [
            'Authorization: key=' . $serverKey,
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200) {
            \Log::info('Legacy FCM notification sent successfully for user: ' . $this->user_id);
            return true;
        } else {
            \Log::error('Legacy FCM failed with HTTP code: ' . $httpCode . ', Response: ' . $response);
            return false;
        }
    }

    /**
     * Send using FCM v1 API (modern approach)
     */
    private function sendFCMv1($user): bool
    {
        try {
            $accessToken = $this->getFCMAccessToken();
            $projectId = "testing-398711";

            if (empty($accessToken) || empty($projectId)) {
                \Log::error('FCM access token or project ID not available');
                return false;
            }

            $message = [
                "message" => [
                    "token" => $user->device_token,
                    "notification" => [
                        "title" => $this->title,
                        "body" => $this->body,
                        "image" => $this->icon
                    ],
                    "data" => $this->data,
                    "android" => [
                        "priority" => "high",
                        "notification" => [
                            "icon" => "ic_notification",
                            "color" => "#FF6B35",
                            "channel_id" => "high_importance_channel",
                            "click_action" => $this->action
                        ]
                    ]
                ]
            ];

            $headers = [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json',
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode == 200) {
                \Log::info('FCM v1 notification sent successfully for user: ' . $this->user_id);
                return true;
            } else {
                \Log::error('FCM v1 failed with HTTP code: ' . $httpCode . ', Response: ' . $response);
                return false;
            }
        } catch (\Exception $e) {
            \Log::error('FCM v1 exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get FCM Access Token using Service Account
     */
    private function getFCMAccessToken(): ?string
    {
        try {
            // Method 1: Using Google Auth Library (Recommended)
            if (class_exists('Google\Auth\Credentials\ServiceAccountCredentials')) {
                return $this->getAccessTokenWithGoogleAuth();
            }

            // Method 2: Manual JWT approach (if Google Auth not available)
            return $this->getAccessTokenManual();

        } catch (\Exception $e) {
            \Log::error('Error getting FCM access token: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get access token using Google Auth library
     */
    private function getAccessTokenWithGoogleAuth(): ?string
    {
        $serviceAccountPath = (base_path('config/firebase.json'));

        if (!$serviceAccountPath || !file_exists($serviceAccountPath)) {
            \Log::error('Firebase service account file not found');
            return null;
        }

        $credentials = new ServiceAccountCredentials(
            ['https://www.googleapis.com/auth/firebase.messaging'],
            $serviceAccountPath
        );

        $httpHandler = HttpHandlerFactory::build();
        $token = $credentials->fetchAuthToken($httpHandler);

        return $token['access_token'] ?? null;
    }

    /**
     * Manual JWT approach for getting access token
     */
    private function getAccessTokenManual(): ?string
    {
        $serviceAccountPath = config('services.firebase.service_account_path');

        if (!$serviceAccountPath || !file_exists($serviceAccountPath)) {
            return null;
        }

        $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);

        if (!$serviceAccount) {
            return null;
        }

        // Create JWT header
        $header = json_encode(['typ' => 'JWT', 'alg' => 'RS256']);

        // Create JWT payload
        $now = time();
        $payload = json_encode([
            'iss' => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600
        ]);

        // Encode
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        // Create signature
        $signature = '';
        openssl_sign(
            $base64Header . '.' . $base64Payload,
            $signature,
            $serviceAccount['private_key'],
            'SHA256'
        );
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        // Create JWT
        $jwt = $base64Header . '.' . $base64Payload . '.' . $base64Signature;

        // Exchange JWT for access token
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200) {
            $data = json_decode($response, true);
            return $data['access_token'] ?? null;
        }

        return null;
    }
}
