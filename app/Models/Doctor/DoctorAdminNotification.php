<?php

namespace App\Models\Doctor;

use Illuminate\Database\Eloquent\Model;

class DoctorAdminNotification extends Model
{
    protected $fillable = [
        'doctor_appointment_id',
        'event',
        'title',
        'message',
        'is_read',
    ];
}
