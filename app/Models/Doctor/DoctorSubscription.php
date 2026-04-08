<?php

namespace App\Models\Doctor;

use Illuminate\Database\Eloquent\Model;

class DoctorSubscription extends Model
{
    protected $fillable = [
        'doctor_id',
        'doctor_plan_id',
        'start_date',
        'due_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function plan()
    {
        return $this->belongsTo(DoctorPlan::class, 'doctor_plan_id');
    }
}

