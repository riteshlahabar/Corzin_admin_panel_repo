<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Farmer\Animal;

class AnimalBuySellController extends Controller
{
    public function index()
    {
        $sellingAnimals = Animal::query()
            ->with(['farmer', 'animalType', 'pan', 'motherAnimal'])
            ->where('is_for_sale', true)
            ->latest('listed_for_sale_at')
            ->latest()
            ->get();

        return view('shop.animal_buy_sell', compact('sellingAnimals'));
    }

    public function cancel(Animal $animal)
    {
        if (! (bool) $animal->is_for_sale) {
            return redirect()->route('shop.animal_buy_sell')->with('success', 'Animal is not listed for sale.');
        }

        $animal->update([
            'is_for_sale' => false,
            'selling_price' => null,
            'listed_for_sale_at' => null,
        ]);

        return redirect()->route('shop.animal_buy_sell')->with('success', 'Animal selling cancelled successfully.');
    }
}
