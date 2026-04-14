<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Model;

class ShopAdminNotification extends Model
{
    protected $fillable = [
        'shop_order_id',
        'title',
        'message',
        'is_read',
    ];
}
