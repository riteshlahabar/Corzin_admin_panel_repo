<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\Farmer\FeedSubtype;
use App\Models\Farmer\FeedType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeedTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = FeedType::query()
            ->whereNull('farmer_id')
            ->with(['subtypes' => fn ($q) => $q->orderBy('sort_order')->orderBy('name')])
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = strtolower(trim((string) $request->search));
            $query->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
        }

        $feedTypes = $query->paginate(20)->withQueryString();

        return view('settings.feed_types', compact('feedTypes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'default_unit' => ['required', 'in:Kg,Gram'],
            'package_quantity' => ['required', 'numeric', 'min:0.01'],
            'subtypes_text' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $name = trim($data['name']);
        $exists = FeedType::query()
            ->whereNull('farmer_id')
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->exists();
        if ($exists) {
            return back()->withErrors(['name' => 'Feed type already exists.'])->withInput();
        }

        $subtypes = $this->parseSubtypeText($data['subtypes_text']);
        if (empty($subtypes)) {
            return back()->withErrors(['subtypes_text' => 'Please enter at least one subtype.'])->withInput();
        }

        DB::transaction(function () use ($data, $name, $subtypes) {
            $type = FeedType::create([
                'farmer_id' => null,
                'name' => $name,
                'default_unit' => $data['default_unit'],
                'package_quantity' => (float) $data['package_quantity'],
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            $rows = [];
            foreach ($subtypes as $index => $subtypeName) {
                $rows[] = [
                    'feed_type_id' => $type->id,
                    'name' => $subtypeName,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            FeedSubtype::insert($rows);
        });

        return back()->with('success', 'Feed type added successfully.');
    }

    public function update(Request $request, FeedType $feedType)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'default_unit' => ['required', 'in:Kg,Gram'],
            'package_quantity' => ['required', 'numeric', 'min:0.01'],
            'subtypes_text' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $name = trim($data['name']);
        $exists = FeedType::query()
            ->whereNull('farmer_id')
            ->where('id', '!=', $feedType->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->exists();
        if ($exists) {
            return back()->withErrors(['name' => 'Feed type already exists.'])->withInput();
        }

        $subtypes = $this->parseSubtypeText($data['subtypes_text']);
        if (empty($subtypes)) {
            return back()->withErrors(['subtypes_text' => 'Please enter at least one subtype.'])->withInput();
        }

        DB::transaction(function () use ($feedType, $data, $name, $subtypes) {
            $feedType->update([
                'name' => $name,
                'default_unit' => $data['default_unit'],
                'package_quantity' => (float) $data['package_quantity'],
                'is_active' => (bool) ($data['is_active'] ?? false),
            ]);

            FeedSubtype::where('feed_type_id', $feedType->id)->delete();
            $rows = [];
            foreach ($subtypes as $index => $subtypeName) {
                $rows[] = [
                    'feed_type_id' => $feedType->id,
                    'name' => $subtypeName,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            FeedSubtype::insert($rows);
        });

        return back()->with('success', 'Feed type updated successfully.');
    }

    public function toggle(FeedType $feedType)
    {
        $feedType->update([
            'is_active' => ! (bool) $feedType->is_active,
        ]);

        return back()->with('success', 'Feed type status updated.');
    }

    /**
     * @return array<int, string>
     */
    private function parseSubtypeText(string $text): array
    {
        $parts = preg_split('/[\r\n,]+/', $text) ?: [];
        $clean = collect($parts)
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique(fn ($value) => mb_strtolower($value))
            ->values()
            ->all();

        return array_values($clean);
    }
}

