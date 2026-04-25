<?php

namespace App\Models\Farmer;

use Illuminate\Database\Eloquent\Model;

class FarmerPan extends Model
{
    protected $fillable = [
        'farmer_id',
        'name',
    ];

    public function farmer()
    {
        return $this->belongsTo(Farmer::class);
    }

    public function animals()
    {
        return $this->hasMany(Animal::class, 'pan_id');
    }

    public function fromTransfers()
    {
        return $this->hasMany(AnimalLifecycleHistory::class, 'from_pan_id');
    }

    public function toTransfers()
    {
        return $this->hasMany(AnimalLifecycleHistory::class, 'to_pan_id');
    }
}

