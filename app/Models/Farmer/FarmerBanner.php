<?php

namespace App\Models\Farmer;

use Illuminate\Database\Eloquent\Model;

class FarmerBanner extends Model
{
    protected $fillable = [
        'title',
        'image_path',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
