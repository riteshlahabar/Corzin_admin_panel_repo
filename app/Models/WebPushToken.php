<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebPushToken extends Model
{
    protected $fillable = [
        'token',
        'device_name',
        'user_agent',
        'is_active',
        'last_seen_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_seen_at' => 'datetime',
    ];
}

