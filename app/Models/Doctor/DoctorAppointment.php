<?php

namespace App\Models\Doctor;

use App\Models\Farmer\Animal;
use App\Models\Farmer\Farmer;
use Illuminate\Database\Eloquent\Model;

class DoctorAppointment extends Model
{
    protected $fillable = [
        'doctor_id',
        'appointment_group_id',
        'farmer_id',
        'animal_id',
        'farmer_name',
        'farmer_phone',
        'animal_name',
        'animal_photo',
        'concern',
        'disease_ids',
        'disease_details',
        'otp_code',
        'otp_verified_at',
        'treatment_started_at',
        'treatment_details',
        'onsite_treatment',
        'followup_required',
        'next_followup_date',
        'doctor_live_latitude',
        'doctor_live_longitude',
        'doctor_live_updated_at',
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
        'followup_notified_on',
    ];

    protected $casts = [
        'disease_ids' => 'array',
        'followup_required' => 'boolean',
        'requested_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'farmer_approved_at' => 'datetime',
        'completed_at' => 'datetime',
        'otp_verified_at' => 'datetime',
        'treatment_started_at' => 'datetime',
        'next_followup_date' => 'date',
        'followup_notified_on' => 'date',
        'doctor_live_updated_at' => 'datetime',
        'charges' => 'decimal:2',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'doctor_live_latitude' => 'decimal:7',
        'doctor_live_longitude' => 'decimal:7',
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

    public function getAppointmentCodeAttribute(): string
    {
        $id = (int) ($this->id ?? 0);
        if ($id <= 0) {
            return 'C/APP/00';
        }

        return 'C/APP/'.str_pad((string) $id, 2, '0', STR_PAD_LEFT);
    }
}
