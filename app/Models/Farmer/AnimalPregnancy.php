<?php

namespace App\Models\Farmer;

use Illuminate\Database\Eloquent\Model;

class AnimalPregnancy extends Model
{
    protected $fillable = [
        'farmer_id',
        'animal_id',
        'pregnancy_no',
        'service_no',
        'heat_date',
        'ai_date',
        'service_type',
        'bull_name',
        'semen_no',
        'doctor_name',
        'pregnancy_check_due_date',
        'pregnancy_check_date',
        'pregnancy_result',
        'expected_calving_date',
        'dry_off_date',
        'calving_date',
'abort_date',
'abort_reason',
'status',
        'calf_animal_id',
        'notes',
        'is_current',
    ];

    protected $casts = [
        'heat_date' => 'date',
        'ai_date' => 'date',
        'pregnancy_check_due_date' => 'date',
        'pregnancy_check_date' => 'date',
        'expected_calving_date' => 'date',
        'dry_off_date' => 'date',
        'calving_date' => 'date',
'abort_date' => 'date',
'is_current' => 'boolean',
    ];

    public function farmer()
    {
        return $this->belongsTo(Farmer::class);
    }

    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }

    public function calfAnimal()
    {
        return $this->belongsTo(Animal::class, 'calf_animal_id');
    }
}
