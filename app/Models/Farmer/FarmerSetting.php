<?php

namespace App\Models\Farmer;

use Illuminate\Database\Eloquent\Model;

class FarmerSetting extends Model
{
    protected $fillable = [
        'admin_contact_name',
        'admin_contact_number',
    ];
}
