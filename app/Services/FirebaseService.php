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
        $serviceAccountPath = config_path('firebase.json');

        if (! is_file($serviceAccountPath) || filesize($serviceAccountPath) === 0) {
            return;
        }

        try {
            $factory = (new Factory)->withServiceAccount($serviceAccountPath);

            $this->auth = $factory->createAuth();
            $this->messaging = $factory->createMessaging();
            $this->configured = true;
        } catch (Throwable $exception) {
            $this->auth = null;
            $this->messaging = null;
            $this->configured = false;
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
}
