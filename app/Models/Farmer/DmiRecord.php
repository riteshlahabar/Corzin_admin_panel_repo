<?php

namespace App\Models\Farmer;

use Illuminate\Database\Eloquent\Model;

class DmiRecord extends Model
{
    protected $fillable = [
        'farmer_id',
        'animal_id',
        'body_weight',
        'total_milk',
        'required_dmi',
        'actual_dmi',
        'alert_status',
        'date',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'body_weight' => 'float',
        'total_milk' => 'float',
        'required_dmi' => 'float',
        'actual_dmi' => 'float',
    ];

    public function farmer()
    {
        return $this->belongsTo(Farmer::class);
    }

    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }
}
