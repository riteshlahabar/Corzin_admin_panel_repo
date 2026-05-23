<?php

namespace App\Models\Farmer;

use App\Models\Dairy\Dairy;
use Illuminate\Database\Eloquent\Model;

class PanMilkEntry extends Model
{
    protected $fillable = [
        'farmer_id',
        'pan_id',
        'dairy_id',
        'date',
        'shift',
        'quantity_liters',
        'cow_total_liters',
        'fat',
        'snf',
        'rate',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'quantity_liters' => 'decimal:2',
        'cow_total_liters' => 'decimal:2',
        'fat' => 'decimal:2',
        'snf' => 'decimal:2',
        'rate' => 'decimal:2',
    ];

    public function farmer()
    {
        return $this->belongsTo(Farmer::class);
    }

    public function pan()
    {
        return $this->belongsTo(FarmerPan::class, 'pan_id');
    }

    public function dairy()
    {
        return $this->belongsTo(Dairy::class);
    }

    public function details()
    {
        return $this->hasMany(PanMilkEntryDetail::class);
    }
}
