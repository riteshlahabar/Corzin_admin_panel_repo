<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Dairy\Dairy;
use App\Models\Farmer\Farmer;
use Illuminate\Http\Request;

class DairyListController extends Controller
{
    public function index()
    {
        $dairies = Dairy::with('farmer')->latest()->get();
        $farmers = Farmer::orderBy('first_name')->get();

        $summary = [
            'total' => $dairies->count(),
            'active' => $dairies->where('is_active', true)->count(),
            'cities' => $dairies->pluck('city')->filter()->unique()->count(),
        ];

        return view('dairy.index', compact('dairies', 'farmers', 'summary'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        Dairy::create([
            ...$data,
            'village' => $data['city'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('farmer.dairy')->with('success', 'Dairy saved successfully.');
    }

    public function update(Request $request, Dairy $dairy)
    {
        $data = $this->validatedData($request);

        $dairy->update([
            ...$data,
            'village' => $data['city'] ?? null,
            'is_active' => $request->boolean('is_active', false),
        ]);

        return redirect()->route('farmer.dairy')->with('success', 'Dairy updated successfully.');
    }

    public function destroy(Dairy $dairy)
    {
        $dairy->delete();

        return redirect()->route('farmer.dairy')->with('success', 'Dairy deleted successfully.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'farmer_id' => 'required|exists:farmers,id',
            'dairy_name' => 'required|string|max:255',
            'gst_no' => 'nullable|string|max:50',
            'contact_number' => 'nullable|string|max:30',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'taluka' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'pincode' => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
        ]);
    }
}
