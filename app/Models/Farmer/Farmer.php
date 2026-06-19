<?php

namespace App\Models\Farmer;

use App\Models\Dairy\Dairy;
use App\Models\Doctor\Doctor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Farmer extends Model
{
    protected $fillable = [
        'mobile',
        'referral_code',
        'referral_points',
        'referred_by_doctor_id',
        'doctor_referral_code',
        'referral_reward_granted_at',
        'referred_by_farmer_id',
        'farmer_referral_code',
        'farmer_referral_reward_granted_at',
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
        'farmer_referral_reward_granted_at' => 'datetime',
        'active_session_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $farmer): void {
            if (blank($farmer->referral_code)) {
                $farmer->referral_code = self::generateUniqueReferralCode();
            } else {
                $farmer->referral_code = strtoupper(trim((string) $farmer->referral_code));
            }
        });
    }

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

    public function referredByFarmer()
    {
        return $this->belongsTo(self::class, 'referred_by_farmer_id');
    }

    public function referredFarmers()
    {
        return $this->hasMany(self::class, 'referred_by_farmer_id');
    }

    public function ensureReferralCode(): string
    {
        if (blank($this->referral_code)) {
            $this->referral_code = self::generateUniqueReferralCode();
            $this->save();
        }

        return (string) $this->referral_code;
    }

    public static function generateUniqueReferralCode(): string
    {
        do {
            $code = 'FRM'.strtoupper(Str::random(7));
        } while (self::query()->where('referral_code', $code)->exists());

        return $code;
    }
}

