<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\Farmer\FeedType;
use Illuminate\Http\Request;

class FeedTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = FeedType::query()->orderBy('name');

        if ($request->filled('search')) {
            $search = strtolower(trim((string) $request->search));
            $query->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
        }

        $feedTypes = $query->paginate($this->tablePerPage($request))->withQueryString();

        return view('settings.feed_types', compact('feedTypes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'default_unit' => ['required', 'string', 'max:30'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $name = trim((string) $data['name']);
        $exists = FeedType::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->exists();

        if ($exists) {
            return back()->withErrors(['name' => 'Feed type already exists.'])->withInput();
        }

        FeedType::create([
            'name' => $name,
            'default_unit' => trim((string) $data['default_unit']),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return back()->with('success', 'Feed type added successfully.');
    }

    public function update(Request $request, FeedType $feedType)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'default_unit' => ['required', 'string', 'max:30'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $name = trim((string) $data['name']);
        $exists = FeedType::query()
            ->where('id', '!=', $feedType->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->exists();

        if ($exists) {
            return back()->withErrors(['name' => 'Feed type already exists.'])->withInput();
        }

        $feedType->update([
            'name' => $name,
            'default_unit' => trim((string) $data['default_unit']),
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return back()->with('success', 'Feed type updated successfully.');
    }

    public function toggle(FeedType $feedType)
    {
        $feedType->update([
            'is_active' => ! (bool) $feedType->is_active,
        ]);

        return back()->with('success', 'Feed type status updated.');
    }
}
