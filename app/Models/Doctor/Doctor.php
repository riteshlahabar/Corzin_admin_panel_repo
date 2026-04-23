<?php

namespace App\Models\Doctor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Doctor extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'clinic_name',
        'degree',
        'contact_number',
        'email',
        'adhar_number',
        'pan_number',
        'mmc_registration_number',
        'clinic_registration_number',
        'clinic_address',
        'village',
        'city',
        'taluka',
        'district',
        'state',
        'pincode',
        'latitude',
        'longitude',
        'adhar_document',
        'adhar_document_back',
        'pan_document',
        'mmc_document',
        'clinic_registration_document',
        'doctor_photo',
        'status',
        'is_active_for_appointments',
        'status_message',
        'password',
        'terms_accepted',
        'terms_text',
        'fcm_token',
        'approved_at',
        'last_live_location_at',
        'live_location_address',
    ];

    protected $casts = [
        'terms_accepted' => 'boolean',
        'is_active_for_appointments' => 'boolean',
        'approved_at' => 'datetime',
        'last_live_location_at' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    protected $hidden = [
        'password',
    ];

    public function setPasswordAttribute($value): void
    {
        if (blank($value)) {
            return;
        }

        $this->attributes['password'] = str_starts_with($value, '$2y$')
            ? $value
            : Hash::make($value);
    }

    public function getFullNameAttribute(): string
    {
        return trim(collect([
            $this->first_name,
            $this->last_name,
        ])->filter()->join(' '));
    }

    public function documents(): array
    {
        return [
            'adhar_document' => $this->documentUrl($this->adhar_document),
            'adhar_document_front' => $this->documentUrl($this->adhar_document),
            'adhar_document_back' => $this->documentUrl($this->adhar_document_back),
            'pan_document' => $this->documentUrl($this->pan_document),
            'mmc_document' => $this->documentUrl($this->mmc_document),
            'clinic_registration_document' => $this->documentUrl($this->clinic_registration_document),
        ];
    }

    public function doctorPhotoUrl(): ?string
    {
        return $this->documentUrl($this->doctor_photo);
    }

    public function appointments()
    {
        return $this->hasMany(DoctorAppointment::class);
    }

    public function subscription()
    {
        return $this->hasOne(DoctorSubscription::class);
    }

    protected function documentUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return asset($path);
    }
}
