<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Farmer\Animal;

class AnimalBuySellController extends Controller
{
    public function index()
    {
        $sellingAnimals = Animal::query()
            ->with(['farmer', 'animalType'])
            ->where('is_for_sale', true)
            ->latest('listed_for_sale_at')
            ->latest()
            ->get();

        return view('shop.animal_buy_sell', compact('sellingAnimals'));
    }
}

