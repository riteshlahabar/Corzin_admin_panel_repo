<?php

namespace App\Models\Farmer;

use App\Models\Dairy\Dairy;
use App\Models\Doctor\Doctor;
use Illuminate\Database\Eloquent\Model;

class Farmer extends Model
{
    protected $fillable = [
        'mobile',
        'referred_by_doctor_id',
        'doctor_referral_code',
        'referral_reward_granted_at',
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
        'active_device_id',
        'active_session_token',
        'active_session_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
        'referral_reward_granted_at' => 'datetime',
        'active_session_at' => 'datetime',
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

    public function referredByDoctor()
    {
        return $this->belongsTo(Doctor::class, 'referred_by_doctor_id');
    }
}

