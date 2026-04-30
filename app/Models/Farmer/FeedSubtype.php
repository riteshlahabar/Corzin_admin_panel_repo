<?php

namespace App\Models\Farmer;

use Illuminate\Database\Eloquent\Model;

class FeedSubtype extends Model
{
    protected $fillable = [
        'feed_type_id',
        'name',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function feedType()
    {
        return $this->belongsTo(FeedType::class, 'feed_type_id');
    }
}

