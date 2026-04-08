<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farmer\Farmer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FarmerListController extends Controller
{
    public function index()
    {
        $farmers = Farmer::latest()->get();

        return view('farmer.index', compact('farmers'));
    }

    public function create()
    {
        return view('farmer.form', [
            'farmer' => new Farmer(),
            'formTitle' => 'Add Farmer',
            'formAction' => route('farmer.store'),
            'isEdit' => false,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateFarmer($request);
        $data['is_active'] = $request->boolean('is_active', true);

        Farmer::create($data);

        return redirect()->route('farmer.list')->with('success', 'Farmer added successfully.');
    }

    public function edit(Farmer $farmer)
    {
        return view('farmer.form', [
            'farmer' => $farmer,
            'formTitle' => 'Edit Farmer',
            'formAction' => route('farmer.update', $farmer),
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, Farmer $farmer)
    {
        $data = $this->validateFarmer($request, $farmer->id);
        $data['is_active'] = $request->boolean('is_active');

        $farmer->update($data);

        return redirect()->route('farmer.list')->with('success', 'Farmer updated successfully.');
    }

    public function toggle(Farmer $farmer)
    {
        $farmer->update([
            'is_active' => ! $farmer->is_active,
        ]);

        return redirect()->route('farmer.list')->with('success', 'Farmer status updated successfully.');
    }

    private function validateFarmer(Request $request, ?int $farmerId = null): array
    {
        return $request->validate([
            'mobile' => ['required', 'string', 'max:20', Rule::unique('farmers', 'mobile')->ignore($farmerId)],
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'village' => 'required|string|max:255',
            'city' => 'nullable|string|max:255',
            'taluka' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'pincode' => 'nullable|string|max:20',
        ]);
    }
}
