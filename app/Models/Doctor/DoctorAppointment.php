<?php

namespace App\Models\Doctor;

use App\Models\Farmer\Animal;
use App\Models\Farmer\Farmer;
use Illuminate\Database\Eloquent\Model;

class DoctorAppointment extends Model
{
    protected $fillable = [
        'doctor_id',
        'farmer_id',
        'animal_id',
        'farmer_name',
        'farmer_phone',
        'animal_name',
        'animal_photo',
        'concern',
        'status',
        'requested_at',
        'scheduled_at',
        'charges',
        'latitude',
        'longitude',
        'address',
        'notes',
        'farmer_approved_at',
        'completed_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'farmer_approved_at' => 'datetime',
        'completed_at' => 'datetime',
        'charges' => 'decimal:2',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function farmer()
    {
        return $this->belongsTo(Farmer::class);
    }

    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }

    public function getAnimalPhotoUrlAttribute(): ?string
    {
        if (blank($this->animal_photo)) {
            return null;
        }

        return asset($this->animal_photo);
    }
}

