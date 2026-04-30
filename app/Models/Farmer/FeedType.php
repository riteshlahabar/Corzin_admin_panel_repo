<?php

namespace App\Models\Farmer;

use Illuminate\Database\Eloquent\Model;

class FeedType extends Model
{
    protected $fillable = [
        'farmer_id',
        'name',
        'default_unit',
        'package_quantity',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'package_quantity' => 'decimal:2',
    ];

    public function feedingRecords()
    {
        return $this->hasMany(FeedingRecord::class);
    }

    public function subtypes()
    {
        return $this->hasMany(FeedSubtype::class, 'feed_type_id')->orderBy('sort_order')->orderBy('name');
    }
}
