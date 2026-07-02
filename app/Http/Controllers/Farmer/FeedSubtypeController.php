<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farmer\Farmer;
use App\Models\Farmer\FeedSubtype;
use App\Models\Farmer\FeedType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeedSubtypeController extends Controller
{
    public function index(Request $request)
    {
        $query = FeedSubtype::query()
            ->with(['farmer', 'feedType'])
            ->whereNotNull('farmer_id')
            ->latest('updated_at')
            ->latest('id');

        if ($request->filled('search')) {
            $search = mb_strtolower(trim((string) $request->input('search')));
            $query->where(function ($nested) use ($search) {
                $nested->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                    ->orWhereHas('feedType', function ($feedTypeQuery) use ($search) {
                        $feedTypeQuery->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
                    })
                    ->orWhereHas('farmer', function ($farmerQuery) use ($search) {
                        $farmerQuery->whereRaw(
                            "LOWER(CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,''), ' ', COALESCE(mobile,''))) LIKE ?",
                            ["%{$search}%"]
                        );
                    });
            });
        }

        if ($request->filled('farmer_id')) {
            $query->where('farmer_id', (int) $request->input('farmer_id'));
        }

        if ($request->filled('feed_type_id')) {
            $query->where('feed_type_id', (int) $request->input('feed_type_id'));
        }

        if ($request->filled('status')) {
            $status = strtolower(trim((string) $request->input('status')));
            if (in_array($status, ['active', 'inactive'], true)) {
                $query->where('is_active', $status === 'active');
            }
        }

        $subtypes = $query->paginate($this->tablePerPage($request))->withQueryString();
        $farmers = Farmer::query()->orderBy('first_name')->orderBy('last_name')->get();
        $feedTypes = FeedType::query()->where('is_active', true)->orderBy('name')->get();

        return view('farmer.feed_subtypes.index', compact('subtypes', 'farmers', 'feedTypes'));
    }

    public function create()
    {
        $farmers = Farmer::query()->orderBy('first_name')->orderBy('last_name')->get();
        $feedTypes = FeedType::query()->where('is_active', true)->orderBy('name')->get();

        return view('farmer.feed_subtypes.create', compact('farmers', 'feedTypes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'farmer_id' => ['required', 'exists:farmers,id'],
            'feed_type_id' => ['required', 'exists:feed_types,id'],
            'subtype_names' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $names = $this->parseSubtypeText((string) $data['subtype_names']);
        if (empty($names)) {
            return back()->withErrors(['subtype_names' => 'Please enter at least one subtype name.'])->withInput();
        }

        $farmerId = (int) $data['farmer_id'];
        $feedTypeId = (int) $data['feed_type_id'];
        $isActive = (bool) ($data['is_active'] ?? true);
        $createdCount = 0;

        DB::transaction(function () use ($names, $farmerId, $feedTypeId, $isActive, &$createdCount) {
            $nextSort = ((int) FeedSubtype::query()
                ->where('feed_type_id', $feedTypeId)
                ->where('farmer_id', $farmerId)
                ->max('sort_order')) + 1;

            foreach ($names as $name) {
                $exists = FeedSubtype::query()
                    ->where('feed_type_id', $feedTypeId)
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                    ->where(function ($query) use ($farmerId) {
                        $query->whereNull('farmer_id')
                            ->orWhere('farmer_id', $farmerId);
                    })
                    ->exists();

                if ($exists) {
                    continue;
                }

                FeedSubtype::create([
                    'feed_type_id' => $feedTypeId,
                    'farmer_id' => $farmerId,
                    'name' => $name,
                    'is_active' => $isActive,
                    'sort_order' => $nextSort++,
                ]);

                $createdCount++;
            }
        });

        if ($createdCount === 0) {
            return back()->withErrors([
                'subtype_names' => 'All entered subtypes already exist for this farmer and feed type.',
            ])->withInput();
        }

        return redirect()
            ->route('farmer.feed-subtypes.index')
            ->with('success', $createdCount.' feed subtype(s) added successfully.');
    }

    public function edit(FeedSubtype $feedSubtype)
    {
        abort_if(! $feedSubtype->farmer_id, 404);

        $farmers = Farmer::query()->orderBy('first_name')->orderBy('last_name')->get();
        $feedTypes = FeedType::query()->where('is_active', true)->orderBy('name')->get();

        return view('farmer.feed_subtypes.edit', compact('feedSubtype', 'farmers', 'feedTypes'));
    }

    public function update(Request $request, FeedSubtype $feedSubtype)
    {
        abort_if(! $feedSubtype->farmer_id, 404);

        $data = $request->validate([
            'farmer_id' => ['required', 'exists:farmers,id'],
            'feed_type_id' => ['required', 'exists:feed_types,id'],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $name = trim((string) $data['name']);
        $farmerId = (int) $data['farmer_id'];
        $feedTypeId = (int) $data['feed_type_id'];

        $exists = FeedSubtype::query()
            ->where('id', '!=', $feedSubtype->id)
            ->where('feed_type_id', $feedTypeId)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->where(function ($query) use ($farmerId) {
                $query->whereNull('farmer_id')
                    ->orWhere('farmer_id', $farmerId);
            })
            ->exists();

        if ($exists) {
            return back()->withErrors(['name' => 'Subtype already exists for this feed type.'])->withInput();
        }

        $feedSubtype->update([
            'farmer_id' => $farmerId,
            'feed_type_id' => $feedTypeId,
            'name' => $name,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return redirect()
            ->route('farmer.feed-subtypes.index')
            ->with('success', 'Feed subtype updated successfully.');
    }

    public function toggle(FeedSubtype $feedSubtype)
    {
        abort_if(! $feedSubtype->farmer_id, 404);

        $feedSubtype->update([
            'is_active' => ! (bool) $feedSubtype->is_active,
        ]);

        return back()->with('success', 'Feed subtype status updated.');
    }

    public function destroy(FeedSubtype $feedSubtype)
    {
        abort_if(! $feedSubtype->farmer_id, 404);

        $feedSubtype->delete();

        return back()->with('success', 'Feed subtype deleted successfully.');
    }

    private function parseSubtypeText(string $text): array
    {
        $parts = preg_split('/[\r\n,]+/', $text) ?: [];

        return collect($parts)
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique(fn ($value) => mb_strtolower($value))
            ->values()
            ->all();
    }
}
