<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farmer\Animal;
use App\Models\Farmer\AnimalLifecycleHistory;
use App\Models\Farmer\FarmerPan;
use Illuminate\Http\Request;

class AnimalLifecycleController extends Controller
{
    public function active()
    {
        $animals = Animal::with(['farmer', 'animalType', 'pan'])
            ->where('lifecycle_status', 'active')
            ->where('is_active', true)
            ->latest()
            ->get();

        $panDestinationsMap = FarmerPan::query()
            ->whereIn('farmer_id', $animals->pluck('farmer_id')->filter()->unique()->values())
            ->orderBy('name')
            ->get()
            ->groupBy('farmer_id')
            ->map(fn ($rows) => $rows->map(fn ($pan) => ['id' => $pan->id, 'name' => $pan->name])->values());

        return view('animal_lifecycle.index', [
            'title' => 'Active Animals',
            'rows' => $animals,
            'section' => 'active',
            'dateField' => 'created_at',
            'panDestinationsMap' => $panDestinationsMap,
        ]);
    }

    public function sold()
    {
        $animals = Animal::with(['farmer', 'animalType', 'pan'])
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
        $animals = Animal::with(['farmer', 'animalType', 'pan'])
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

    public function sell(Request $request, Animal $animal)
    {
        $data = $request->validate([
            'selling_price' => 'required|numeric|min:1',
        ]);

        if ((bool) $animal->is_for_sale) {
            return redirect()->route('animal.lifecycle.active')->with('success', 'Animal is already listed for sale.');
        }

        $animal->update([
            'is_for_sale' => true,
            'selling_price' => round((float) $data['selling_price'], 2),
            'listed_for_sale_at' => now(),
        ]);

        return redirect()->route('animal.lifecycle.active')->with('success', 'Animal listed for sale successfully.');
    }

    public function cancelSelling(Animal $animal)
    {
        if (! (bool) $animal->is_for_sale) {
            return redirect()->route('animal.lifecycle.active')->with('success', 'Animal is not listed for sale.');
        }

        $animal->update([
            'is_for_sale' => false,
            'selling_price' => null,
            'listed_for_sale_at' => null,
        ]);

        return redirect()->route('animal.lifecycle.active')->with('success', 'Animal selling cancelled successfully.');
    }

    public function markSold(Animal $animal)
    {
        $previousStatus = $animal->lifecycle_status ?? 'active';

        $animal->update([
            'lifecycle_status' => 'sold',
            'is_active' => false,
            'is_for_sale' => false,
            'selling_price' => null,
            'listed_for_sale_at' => null,
            'sold_at' => now(),
        ]);

        $this->logStatusHistory($animal->fresh(), 'sold', $previousStatus, 'Marked sold from admin active animal screen.');

        return redirect()->route('animal.lifecycle.active')->with('success', 'Animal marked as sold successfully.');
    }

    public function markDeath(Animal $animal)
    {
        $previousStatus = $animal->lifecycle_status ?? 'active';

        $animal->update([
            'lifecycle_status' => 'death',
            'is_active' => false,
            'is_for_sale' => false,
            'selling_price' => null,
            'listed_for_sale_at' => null,
            'death_at' => now(),
        ]);

        $this->logStatusHistory($animal->fresh(), 'death', $previousStatus, 'Marked death from admin active animal screen.');

        return redirect()->route('animal.lifecycle.active')->with('success', 'Animal marked as death successfully.');
    }

    public function transferFromActive(Request $request, Animal $animal)
    {
        $data = $request->validate([
            'to_pan_id' => 'required|integer|exists:farmer_pans,id',
            'notes' => 'nullable|string|max:255',
        ]);

        $toPan = FarmerPan::query()
            ->where('id', (int) $data['to_pan_id'])
            ->where('farmer_id', $animal->farmer_id)
            ->first();

        if (! $toPan) {
            return redirect()->route('animal.lifecycle.active')->with('error', 'Destination Pen not found for this farmer.');
        }

        if ((int) $animal->pan_id === (int) $toPan->id) {
            return redirect()->route('animal.lifecycle.active')->with('success', 'Animal is already in selected Pen.');
        }

        $fromPanId = $animal->pan_id;
        $animal->update(['pan_id' => $toPan->id]);
        $this->logPanTransferHistory(
            $animal->fresh(),
            $fromPanId,
            $toPan->id,
            $data['notes'] ?? 'Transferred from admin active animal screen.'
        );

        return redirect()->route('animal.lifecycle.active')->with('success', 'Animal transferred successfully.');
    }

    private function logStatusHistory(Animal $animal, string $action, string $previousStatus, ?string $notes = null): void
    {
        AnimalLifecycleHistory::create([
            'animal_id' => $animal->id,
            'action_type' => $action,
            'from_status' => $previousStatus,
            'to_status' => $animal->lifecycle_status ?? $action,
            'from_animal_type_id' => $animal->animal_type_id,
            'to_animal_type_id' => $animal->animal_type_id,
            'from_pan_id' => $animal->pan_id,
            'to_pan_id' => $animal->pan_id,
            'notes' => $notes,
            'changed_at' => now(),
        ]);
    }

    private function logPanTransferHistory(Animal $animal, ?int $fromPanId, ?int $toPanId, ?string $notes = null): void
    {
        if ($fromPanId === $toPanId) {
            return;
        }

        AnimalLifecycleHistory::create([
            'animal_id' => $animal->id,
            'action_type' => 'move_pan',
            'from_status' => $animal->lifecycle_status ?? 'active',
            'to_status' => $animal->lifecycle_status ?? 'active',
            'from_animal_type_id' => $animal->animal_type_id,
            'to_animal_type_id' => $animal->animal_type_id,
            'from_pan_id' => $fromPanId,
            'to_pan_id' => $toPanId,
            'notes' => $notes,
            'changed_at' => now(),
        ]);
    }
}
