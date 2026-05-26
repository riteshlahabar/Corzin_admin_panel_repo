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
        'quarter',
        'clinical_type',
        'cmt_score',
        'scc_count',
        'follow_up_date',
        'date',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'follow_up_date' => 'date',
        'scc_count' => 'decimal:2',
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
