<?php

namespace App\Models\Farmer;

use Illuminate\Database\Eloquent\Model;

class FeedingRecord extends Model
{
    protected $fillable = [
        'farmer_id',
        'animal_id',
        'feed_type_id',
        'quantity',
        'unit',
        'feeding_time',
        'date',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'quantity' => 'decimal:2',
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
        return $this->belongsTo(FeedType::class);
    }
}
