<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farmer\Animal;
use App\Models\Farmer\AnimalLifecycleHistory;

class AnimalLifecycleController extends Controller
{
    public function active()
    {
        $animals = Animal::with(['farmer', 'animalType'])
            ->where('lifecycle_status', 'active')
            ->where('is_active', true)
            ->latest()
            ->get();

        return view('animal_lifecycle.index', [
            'title' => 'Active Animals',
            'rows' => $animals,
            'section' => 'active',
            'dateField' => 'created_at',
        ]);
    }

    public function sold()
    {
        $animals = Animal::with(['farmer', 'animalType'])
            ->where('lifecycle_status', 'sold')
            ->latest('sold_at')
            ->get();

        return view('animal_lifecycle.index', [
            'title' => 'Sold Animals',
            'rows' => $animals,
            'section' => 'sold',
            'dateField' => 'sold_at',
        ]);
    }

    public function death()
    {
        $animals = Animal::with(['farmer', 'animalType'])
            ->where('lifecycle_status', 'death')
            ->latest('death_at')
            ->get();

        return view('animal_lifecycle.index', [
            'title' => 'Death Animals',
            'rows' => $animals,
            'section' => 'death',
            'dateField' => 'death_at',
        ]);
    }

    public function panTransfer()
    {
        $rows = AnimalLifecycleHistory::with(['animal.farmer', 'animal.animalType', 'fromAnimalType', 'toAnimalType', 'fromPan', 'toPan'])
            ->whereIn('action_type', ['move_type', 'move_pan'])
            ->latest('changed_at')
            ->get();

        return view('animal_lifecycle.pan_transfer', compact('rows'));
    }
}
