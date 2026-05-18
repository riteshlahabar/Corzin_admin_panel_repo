<?php

namespace App\Models\Dairy;

use Illuminate\Database\Eloquent\Model;

class DairyPaymentEntry extends Model
{
    protected $fillable = [
        'farmer_id',
        'dairy_id',
        'payment_date',
        'opening_balance',
        'day_total_amount',
        'total_amount',
        'paid_amount',
        'closing_balance',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'opening_balance' => 'float',
        'day_total_amount' => 'float',
        'total_amount' => 'float',
        'paid_amount' => 'float',
        'closing_balance' => 'float',
    ];

    public function dairy()
    {
        return $this->belongsTo(Dairy::class);
    }
}
