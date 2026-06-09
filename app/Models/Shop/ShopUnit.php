<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Model;

class ShopUnit extends Model
{
    protected $fillable = [
        'name',
    ];

    public function getRouteKeyName(): string
    {
        return 'name';
    }
}
