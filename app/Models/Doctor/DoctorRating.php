<?php

namespace App\Models\Doctor;

use App\Models\Farmer\Farmer;
use Illuminate\Database\Eloquent\Model;

class DoctorRating extends Model
{
    protected $fillable = [
        'doctor_appointment_id',
        'doctor_id',
        'farmer_id',
        'rating',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    public function appointment()
    {
        return $this->belongsTo(DoctorAppointment::class, 'doctor_appointment_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function farmer()
    {
        return $this->belongsTo(Farmer::class);
    }
}
