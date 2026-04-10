<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

class FirebaseService
{
    protected $auth;
    protected $messaging;
    protected bool $configured = false;

    public function __construct()
    {
        try {
            $credentials = $this->resolveServiceAccountCredentials();
            if ($credentials === null) {
                Log::warning('FCM init skipped: service account credentials not found.');
                return;
            }

            $factory = (new Factory)->withServiceAccount($credentials);

            $this->auth = $factory->createAuth();
            $this->messaging = $factory->createMessaging();
            $this->configured = true;
            Log::info('FCM init success: Firebase messaging configured.');
        } catch (Throwable $exception) {
            $this->auth = null;
            $this->messaging = null;
            $this->configured = false;
            Log::error('FCM init failed', ['error' => $exception->getMessage()]);
        }
    }

    public function verifyToken($idToken)
    {
        if (! $this->configured || ! $this->auth) {
            throw new \RuntimeException('Firebase authentication is not configured.');
        }

        return $this->auth->verifyIdToken($idToken);
    }

    public function sendToDevice(?string $token, string $title, string $body, array $data = []): void
    {
        if (! $this->configured || ! $this->messaging) {
            Log::warning('FCM skipped: Firebase is not configured.');
            return;
        }

        if (blank($token)) {
            Log::warning('FCM skipped: target token is empty.');
            return;
        }

        $normalizedData = [];
        foreach ($data as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $normalizedData[(string) $key] = (string) ($value ?? '');
                continue;
            }

            $encoded = json_encode($value);
            $normalizedData[(string) $key] = $encoded === false ? '' : $encoded;
        }

        try {
            $message = CloudMessage::new()
                ->withToken((string) $token)
                ->withNotification(Notification::create($title, $body))
                ->withData($normalizedData);

            $this->messaging->send($message);
        } catch (Throwable $exception) {
            Log::error('FCM send failed', [
                'token_prefix' => substr((string) $token, 0, 16),
                'title' => $title,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    protected function resolveServiceAccountCredentials(): array|string|null
    {
        // 1) Preferred: explicit env path
        $envPath = trim((string) env('FIREBASE_CREDENTIALS'));
        if ($envPath !== '' && is_file($envPath) && filesize($envPath) > 0) {
            return $envPath;
        }

        // 2) Optional: raw JSON in env (advanced setup)
        $envJson = trim((string) env('FIREBASE_CREDENTIALS_JSON'));
        if ($envJson !== '') {
            $decoded = json_decode($envJson, true);
            if (is_array($decoded) && ! empty($decoded['client_email'])) {
                return $decoded;
            }
        }

        // 3) File fallbacks commonly used in shared hosting deployments
        $candidates = [
            config_path('firebase.json'),
            base_path('firebase.json'),
            storage_path('app/firebase.json'),
            public_path('firebase.json'),
        ];

        foreach ($candidates as $path) {
            if (is_file($path) && filesize($path) > 0) {
                return $path;
            }
        }

        return null;
    }
}
