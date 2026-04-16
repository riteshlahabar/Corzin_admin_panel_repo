<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    protected $fillable = [
        'template_key',
        'template_name',
        'title_template',
        'body_template',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}

