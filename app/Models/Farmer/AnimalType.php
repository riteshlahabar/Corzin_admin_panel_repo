<?php

namespace App\Models\Farmer;

use Illuminate\Database\Eloquent\Model;

class AnimalType extends Model
{
    protected $table = 'animal_types';

    protected $fillable = [
        'name'
    ];

    /**
     * Relationship: One AnimalType has many Animals
     */
    public function animals()
    {
        return $this->hasMany(\App\Models\Farmer\Animal::class, 'animal_type_id');
    }
}