<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WebPushToken;
use Illuminate\Http\Request;

class WebPushTokenController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'min:20'],
            'device_name' => ['nullable', 'string', 'max:120'],
            'user_agent' => ['nullable', 'string'],
        ]);

        WebPushToken::query()->updateOrCreate(
            ['token' => $data['token']],
            [
                'device_name' => $data['device_name'] ?? 'Admin Web',
                'user_agent' => $data['user_agent'] ?? null,
                'is_active' => true,
                'last_seen_at' => now(),
            ]
        );

        return response()->json([
            'status' => true,
            'message' => 'Web push token registered successfully.',
        ]);
    }
}

