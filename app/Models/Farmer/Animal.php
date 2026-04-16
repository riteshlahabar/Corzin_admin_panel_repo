<?php

namespace App\Models\Farmer;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Animal extends Model
{
    protected $fillable = [
        'farmer_id',
        'unique_id',
        'animal_name',
        'tag_number',
        'animal_type_id',
        'mother_animal_id',
        'age',
        'birth_date',
        'gender',
        'weight',
        'image',
        'lifecycle_status',
        'is_active',
        'is_for_sale',
        'listed_for_sale_at',
        'sold_at',
        'death_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_for_sale' => 'boolean',
        'listed_for_sale_at' => 'datetime',
        'sold_at' => 'datetime',
        'death_at' => 'datetime',
    ];

    public function farmer()
    {
        return $this->belongsTo(Farmer::class);
    }

    public function milkProductions()
    {
        return $this->hasMany(MilkProduction::class);
    }

    public function animalType()
    {
        return $this->belongsTo(AnimalType::class, 'animal_type_id');
    }

    public function motherAnimal()
    {
        return $this->belongsTo(self::class, 'mother_animal_id');
    }

    public function lifecycleHistories()
    {
        return $this->hasMany(AnimalLifecycleHistory::class)->latest('changed_at');
    }

    public function getCalculatedAgeAttribute(): ?int
    {
        if (! empty($this->birth_date)) {
            return Carbon::parse($this->birth_date)->age;
        }

        return $this->age;
    }

    public function getImageUrlAttribute(): ?string
    {
        if (empty($this->image)) {
            return null;
        }

        $path = str_replace('\\', '/', $this->image);
        $absolutePath = public_path($path);

        if (! is_file($absolutePath)) {
            return null;
        }

        $version = @filemtime($absolutePath) ?: optional($this->updated_at)->timestamp;

        return asset($path) . ($version ? '?v=' . $version : '');
    }
}
