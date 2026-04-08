<?php

namespace App\Models\Reproductive;

use App\Models\Farmer\Animal;
use Illuminate\Database\Eloquent\Model;

class ReproductiveRecord extends Model
{
    protected $fillable = [
        'animal_id',
        'lactation_number',
        'ai_date',
        'breed_name',
        'pregnancy_confirmation',
        'calving_date',
        'notes',
    ];

    protected $casts = [
        'ai_date' => 'date',
        'calving_date' => 'date',
        'pregnancy_confirmation' => 'boolean',
    ];

    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }
}
