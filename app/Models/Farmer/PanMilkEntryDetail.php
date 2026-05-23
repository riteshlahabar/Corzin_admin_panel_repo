<?php

namespace App\Models\Farmer;

use Illuminate\Database\Eloquent\Model;

class PanMilkEntryDetail extends Model
{
    protected $fillable = [
        'pan_milk_entry_id',
        'animal_id',
        'milk_production_id',
        'default_milk_per_session',
        'final_milk_qty',
    ];

    protected $casts = [
        'default_milk_per_session' => 'decimal:2',
        'final_milk_qty' => 'decimal:2',
    ];

    public function panMilkEntry()
    {
        return $this->belongsTo(PanMilkEntry::class);
    }

    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }

    public function milkProduction()
    {
        return $this->belongsTo(MilkProduction::class);
    }
}
