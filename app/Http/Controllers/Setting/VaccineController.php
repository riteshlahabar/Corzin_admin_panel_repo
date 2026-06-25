<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\Farmer\Vaccine;
use Illuminate\Http\Request;

class VaccineController extends Controller
{
    public function index(Request $request)
    {
        $query = Vaccine::query()
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = strtolower(trim((string) $request->search));
            $query->where(function ($builder) use ($search) {
                $builder->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(description) LIKE ?', ["%{$search}%"]);
            });
        }

        $vaccines = $query->paginate($this->tablePerPage($request))->withQueryString();

        return view('settings.vaccines', compact('vaccines'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $name = trim($data['name']);
        $exists = Vaccine::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->exists();

        if ($exists) {
            return back()->withErrors(['name' => 'Vaccine already exists.'])->withInput();
        }

        Vaccine::create([
            'name' => $name,
            'description' => trim((string) ($data['description'] ?? '')),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return back()->with('success', 'Vaccine added successfully.');
    }

    public function update(Request $request, Vaccine $vaccine)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $name = trim($data['name']);
        $exists = Vaccine::query()
            ->where('id', '!=', $vaccine->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->exists();

        if ($exists) {
            return back()->withErrors(['name' => 'Vaccine already exists.'])->withInput();
        }

        $vaccine->update([
            'name' => $name,
            'description' => trim((string) ($data['description'] ?? '')),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return back()->with('success', 'Vaccine updated successfully.');
    }

    public function toggle(Vaccine $vaccine)
    {
        $vaccine->update([
            'is_active' => ! $vaccine->is_active,
        ]);

        return back()->with('success', 'Vaccine status updated.');
    }
}
