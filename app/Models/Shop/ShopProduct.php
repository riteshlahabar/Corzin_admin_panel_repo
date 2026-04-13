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
        'features',
        'image',
        'gallery_images',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'gallery_images' => 'array',
        'is_active' => 'boolean',
    ];

    public function getImageUrlAttribute(): ?string
    {
        if (blank($this->image)) {
            return null;
        }

        return asset($this->image);
    }
}
