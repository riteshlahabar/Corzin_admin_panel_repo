<?php

namespace App\Models\Farmer;

use Illuminate\Database\Eloquent\Model;

class FeedType extends Model
{
    protected $fillable = [
        'name',
        'default_unit',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function feedingRecords()
    {
        return $this->hasMany(FeedingRecord::class);
    }

    public function subtypes()
    {
        return $this->hasMany(FeedSubtype::class, 'feed_type_id')
            ->whereNull('farmer_id')
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function farmerSubtypes()
    {
        return $this->hasMany(FeedSubtype::class, 'feed_type_id')
            ->whereNotNull('farmer_id')
            ->orderBy('sort_order')
            ->orderBy('name');
    }
}
