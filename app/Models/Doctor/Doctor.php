<?php

namespace App\Models\Doctor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class Doctor extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'clinic_name',
        'degree',
        'contact_number',
        'whatsapp_number',
        'email',
        'referral_code',
        'referred_by_doctor_id',
        'referral_points',
        'referral_reward_granted_at',
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
        'referral_reward_granted_at' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    protected $hidden = [
        'password',
    ];

    protected static function booted(): void
    {
        static::creating(function (Doctor $doctor): void {
            if (blank($doctor->referral_code)) {
                $doctor->referral_code = self::generateUniqueReferralCode();
            } else {
                $doctor->referral_code = strtoupper(trim((string) $doctor->referral_code));
            }
        });
    }

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

    public function referredBy()
    {
        return $this->belongsTo(self::class, 'referred_by_doctor_id');
    }

    public function referredDoctors()
    {
        return $this->hasMany(self::class, 'referred_by_doctor_id');
    }

    public function subscription()
    {
        return $this->hasOne(DoctorSubscription::class);
    }

    public function ensureReferralCode(): string
    {
        if (blank($this->referral_code)) {
            $this->referral_code = self::generateUniqueReferralCode();
            $this->save();
        }

        return (string) $this->referral_code;
    }

    protected function documentUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return asset($path);
    }

    public static function generateUniqueReferralCode(): string
    {
        do {
            $code = 'DOC'.strtoupper(Str::random(6));
        } while (self::query()->where('referral_code', $code)->exists());

        return $code;
    }
}
