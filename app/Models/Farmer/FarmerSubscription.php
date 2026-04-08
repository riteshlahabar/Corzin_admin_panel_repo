<?php

namespace App\Models\Farmer;

use Illuminate\Database\Eloquent\Model;

class FarmerSubscription extends Model
{
    protected $fillable = [
        'farmer_id',
        'farmer_plan_id',
        'start_date',
        'due_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
    ];

    public function farmer()
    {
        return $this->belongsTo(Farmer::class);
    }

    public function plan()
    {
        return $this->belongsTo(FarmerPlan::class, 'farmer_plan_id');
    }
}

