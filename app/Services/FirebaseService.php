<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
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
        if (! $this->configured || ! $this->messaging || blank($token)) {
            return;
        }

        $message = CloudMessage::withTarget('token', $token)
            ->withNotification(Notification::create($title, $body))
            ->withData($data);

        $this->messaging->send($message);
    }
}
