<?php

namespace App\Models\Dairy;

use Illuminate\Database\Eloquent\Model;

class DairyPaymentEntry extends Model
{
    protected $fillable = [
        'farmer_id',
        'dairy_id',
        'payment_date',
        'total_amount',
        'paid_amount',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'total_amount' => 'float',
        'paid_amount' => 'float',
    ];

    public function dairy()
    {
        return $this->belongsTo(Dairy::class);
    }
}

