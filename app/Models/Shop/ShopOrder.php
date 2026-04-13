<?php

namespace App\Models\Shop;

use App\Models\Farmer\Farmer;
use Illuminate\Database\Eloquent\Model;

class ShopOrder extends Model
{
    protected $fillable = [
        'farmer_id',
        'farmer_name',
        'farmer_phone',
        'shipping_address',
        'payment_method',
        'payment_status',
        'status',
        'subtotal',
        'delivery_charge',
        'total',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'delivery_charge' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function farmer()
    {
        return $this->belongsTo(Farmer::class);
    }

    public function items()
    {
        return $this->hasMany(ShopOrderItem::class);
    }
}
