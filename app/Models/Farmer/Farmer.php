<?php

namespace App\Models\Farmer;

use App\Models\Dairy\Dairy;
use Illuminate\Database\Eloquent\Model;

class Farmer extends Model
{
    protected $fillable = [
        'mobile',
        'first_name',
        'middle_name',
        'last_name',
        'village',
        'city',
        'taluka',
        'district',
        'state',
        'pincode',
        'latitude',
        'longitude',
        'current_location_address',
        'is_active',
        'fcm_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function animals()
    {
        return $this->hasMany(Animal::class);
    }

    public function pans()
    {
        return $this->hasMany(FarmerPan::class);
    }

    public function dairies()
    {
        return $this->hasMany(Dairy::class);
    }

    public function subscription()
    {
        return $this->hasOne(FarmerSubscription::class);
    }
}

