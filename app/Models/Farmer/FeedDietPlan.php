<?php

namespace App\Models\Farmer;

use Illuminate\Database\Eloquent\Model;

class FeedDietPlan extends Model
{
    protected $fillable = [
        'farmer_id',
        'animal_id',
        'pan_id',
        'diet_plan_name',
        'feed_type_id',
        'reference_date',
        'body_weight',
        'milk_production',
        'target_dmi',
        'planned_dry_matter',
        'dmi_gap',
        'days_count',
        'plan_quantity',
        'consumed_quantity',
        'remaining_quantity',
        'unit',
        'subtype_details',
        'is_active',
    ];

    protected $casts = [
        'reference_date' => 'date',
        'body_weight' => 'decimal:2',
        'milk_production' => 'decimal:2',
        'target_dmi' => 'decimal:2',
        'planned_dry_matter' => 'decimal:2',
        'dmi_gap' => 'decimal:2',
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
