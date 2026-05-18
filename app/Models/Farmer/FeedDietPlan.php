<?php

namespace App\Models\Farmer;

use Illuminate\Database\Eloquent\Model;

class FeedDietPlan extends Model
{
    protected $fillable = [
        'farmer_id',
        'animal_id',
        'diet_plan_name',
        'feed_type_id',
        'days_count',
        'plan_quantity',
        'consumed_quantity',
        'remaining_quantity',
        'unit',
        'subtype_details',
        'is_active',
    ];

    protected $casts = [
        'days_count' => 'integer',
        'plan_quantity' => 'decimal:2',
        'consumed_quantity' => 'decimal:2',
        'remaining_quantity' => 'decimal:2',
        'is_active' => 'boolean',
        'subtype_details' => 'array',
    ];

    public function farmer()
    {
        return $this->belongsTo(Farmer::class);
    }

    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }

    public function feedType()
    {
        return $this->belongsTo(FeedType::class, 'feed_type_id');
    }
}
