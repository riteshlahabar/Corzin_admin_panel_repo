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
        'is_active',
        'fcm_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function animals()
    {
        return $this->hasMany(Animal::class);
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

