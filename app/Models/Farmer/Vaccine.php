<?php

namespace App\Models\Farmer;

use Illuminate\Database\Eloquent\Model;

class Vaccine extends Model
{
    protected $fillable = [
        'name',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function vaccinations()
    {
        return $this->hasMany(AnimalVaccination::class);
    }
}
