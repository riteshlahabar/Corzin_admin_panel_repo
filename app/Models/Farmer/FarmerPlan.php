<?php

namespace App\Models\Farmer;

use Illuminate\Database\Eloquent\Model;

class FarmerPlan extends Model
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
        return $this->hasMany(FarmerSubscription::class);
    }
}

