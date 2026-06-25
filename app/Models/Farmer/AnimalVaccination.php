<?php

namespace App\Models\Farmer;

use Illuminate\Database\Eloquent\Model;

class AnimalVaccination extends Model
{
    protected $fillable = [
        'farmer_id',
        'animal_id',
        'pan_id',
        'pan_name',
        'vaccine_id',
        'doses',
        'vaccination_date',
        'notes',
    ];

    protected $casts = [
        'vaccination_date' => 'date',
    ];

    public function farmer()
    {
        return $this->belongsTo(Farmer::class);
    }

    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }

    public function pan()
    {
        return $this->belongsTo(FarmerPan::class, 'pan_id');
    }

    public function vaccine()
    {
        return $this->belongsTo(Vaccine::class);
    }
}
