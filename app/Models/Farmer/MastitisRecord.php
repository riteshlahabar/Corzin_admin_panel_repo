<?php

namespace App\Models\Farmer;

use Illuminate\Database\Eloquent\Model;

class MastitisRecord extends Model
{
    protected $fillable = [
        'farmer_id',
        'animal_id',
        'test_result',
        'treatment',
        'recovery_status',
        'date',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function farmer()
    {
        return $this->belongsTo(Farmer::class);
    }

    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }
}
