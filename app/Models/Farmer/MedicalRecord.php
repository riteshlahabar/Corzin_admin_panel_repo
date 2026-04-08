<?php

namespace App\Models\Farmer;

use Illuminate\Database\Eloquent\Model;

class MedicalRecord extends Model
{
    protected $fillable = [
        'farmer_id',
        'animal_id',
        'medicine_name',
        'dose',
        'date',
        'disease',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
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
