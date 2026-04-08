<?php

namespace App\Models\Doctor;

use Illuminate\Database\Eloquent\Model;

class DoctorPlan extends Model
{
    protected $fillable = [
        'name',
        'price',
        'duration_days',
        'features',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration_days' => 'integer',
        'is_active' => 'boolean',
    ];

    public function subscriptions()
    {
        return $this->hasMany(DoctorSubscription::class);
    }
}

