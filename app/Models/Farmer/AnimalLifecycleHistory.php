<?php

namespace App\Models\Farmer;

use Illuminate\Database\Eloquent\Model;

class AnimalLifecycleHistory extends Model
{
    protected $fillable = [
        'animal_id',
        'action_type',
        'from_status',
        'to_status',
        'from_animal_type_id',
        'to_animal_type_id',
        'from_pan_id',
        'to_pan_id',
        'notes',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }

    public function fromAnimalType()
    {
        return $this->belongsTo(AnimalType::class, 'from_animal_type_id');
    }

    public function toAnimalType()
    {
        return $this->belongsTo(AnimalType::class, 'to_animal_type_id');
    }

    public function fromPan()
    {
        return $this->belongsTo(FarmerPan::class, 'from_pan_id');
    }

    public function toPan()
    {
        return $this->belongsTo(FarmerPan::class, 'to_pan_id');
    }
}
