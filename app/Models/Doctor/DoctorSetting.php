<?php

namespace App\Models\Doctor;

use Illuminate\Database\Eloquent\Model;

class DoctorSetting extends Model
{
    protected $fillable = [
        'terms_and_conditions',
        'privacy_policy',
    ];
}

