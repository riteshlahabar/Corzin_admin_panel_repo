<?php

namespace App\Models\Dairy;

use App\Models\Farmer\Farmer;
use Illuminate\Database\Eloquent\Model;

class Dairy extends Model
{
    protected $fillable = [
        'farmer_id',
        'dairy_name',
        'gst_no',
        'contact_number',
        'village',
        'address',
        'city',
        'taluka',
        'district',
        'state',
        'pincode',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function farmer()
    {
        return $this->belongsTo(Farmer::class);
    }
}
