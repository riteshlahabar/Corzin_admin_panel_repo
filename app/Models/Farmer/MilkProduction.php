<?php

namespace App\Models\Farmer;

use App\Models\Dairy\Dairy;
use Illuminate\Database\Eloquent\Model;

class MilkProduction extends Model
{
    protected $fillable = [
        'animal_id',
        'dairy_id',
        'date',
        'morning_milk',
        'afternoon_milk',
        'evening_milk',
        'total_milk',
        'fat',
        'snf',
        'rate'
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->total_milk =
                ($model->morning_milk ?? 0) +
                ($model->afternoon_milk ?? 0) +
                ($model->evening_milk ?? 0);
        });
    }

    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }

    public function dairy()
    {
        return $this->belongsTo(Dairy::class);
    }
}
