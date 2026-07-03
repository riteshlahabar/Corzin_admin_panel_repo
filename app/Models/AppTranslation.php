<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppTranslation extends Model
{
    protected $fillable = [
        'group_name',
        'translation_key',
        'en_value',
        'hi_value',
        'mr_value',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}