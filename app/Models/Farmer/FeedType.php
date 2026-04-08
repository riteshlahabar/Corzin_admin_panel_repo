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
}
