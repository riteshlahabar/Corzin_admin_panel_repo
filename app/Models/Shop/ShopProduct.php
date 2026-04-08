<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Model;

class ShopProduct extends Model
{
    protected $fillable = [
        'category',
        'name',
        'subtitle',
        'price',
        'unit',
        'description',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
