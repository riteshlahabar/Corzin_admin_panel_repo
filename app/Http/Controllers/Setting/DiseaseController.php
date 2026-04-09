<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\Doctor\DoctorDisease;
use Illuminate\Http\Request;

class DiseaseController extends Controller
{
    public function index(Request $request)
    {
        $query = DoctorDisease::query()->orderBy('sort_order')->latest('id');

        if ($request->filled('search')) {
            $search = strtolower(trim((string) $request->search));
            $query->where(function ($builder) use ($search) {
                $builder->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(description) LIKE ?', ["%{$search}%"]);
            });
        }

        $diseases = $query->paginate(20)->withQueryString();

        return view('settings.diseases', compact('diseases'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        DoctorDisease::create([
            'name' => trim($data['name']),
            'description' => trim((string) ($data['description'] ?? '')),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return back()->with('success', 'Disease added successfully.');
    }

    public function update(Request $request, DoctorDisease $disease)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $disease->update([
            'name' => trim($data['name']),
            'description' => trim((string) ($data['description'] ?? '')),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return back()->with('success', 'Disease updated successfully.');
    }

    public function toggle(DoctorDisease $disease)
    {
        $disease->update([
            'is_active' => ! $disease->is_active,
        ]);

        return back()->with('success', 'Disease status updated.');
    }
}
