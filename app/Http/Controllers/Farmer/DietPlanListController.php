<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farmer\Animal;
use App\Models\Farmer\Farmer;
use App\Models\Farmer\FarmerPan;
use App\Models\Farmer\FeedType;
use App\Models\Farmer\FeedDietPlan;
use Illuminate\Http\Request;

class DietPlanListController extends Controller
{
    public function index()
    {
        $plans = FeedDietPlan::query()
            ->with(['farmer', 'animal', 'pan', 'feedType'])
            ->latest('reference_date')
            ->latest('id')
            ->get();

        $summary = [
            'total' => $plans->count(),
            'active' => $plans->where('is_active', true)->count(),
            'planned_quantity' => round((float) $plans->sum(fn ($plan) => (float) ($plan->plan_quantity ?? 0)), 2),
        ];

        $farmers = Farmer::query()->orderBy('first_name')->orderBy('last_name')->get();
        $animals = Animal::query()
            ->with(['farmer', 'pan'])
            ->orderBy('animal_name')
            ->get();
        $pans = FarmerPan::query()
            ->with('farmer')
            ->orderBy('name')
            ->get();
        $feedTypes = FeedType::query()
            ->with(['subtypes' => fn ($query) => $query->where('is_active', true)])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('feeding.diet_plans', compact(
            'plans',
            'summary',
            'farmers',
            'animals',
            'pans',
            'feedTypes',
        ));
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request);
        $subtypes = $this->extractSubtypePayload($request);

        if (empty($subtypes)) {
            return back()->withErrors([
                'subtype_details' => 'Please add at least one subtype with quantity and DM%.',
            ])->withInput();
        }

        $animal = Animal::query()
            ->where('id', (int) $data['animal_id'])
            ->where('farmer_id', (int) $data['farmer_id'])
            ->first();

        if (! $animal) {
            return back()->withErrors([
                'animal_id' => 'Selected animal is not valid for this farmer.',
            ])->withInput();
        }

        if (! empty($data['pan_id'])) {
            $pan = FarmerPan::query()
                ->where('id', (int) $data['pan_id'])
                ->where('farmer_id', (int) $data['farmer_id'])
                ->first();

            if (! $pan) {
                return back()->withErrors([
                    'pan_id' => 'Selected pen is not valid for this farmer.',
                ])->withInput();
            }
        }

        $planQuantity = round((float) collect($subtypes)->sum('quantity'), 2);
        $plannedDryMatter = round((float) collect($subtypes)->sum('dry_matter_quantity'), 2);
        $targetDmi = round((float) $data['target_dmi'], 2);

        FeedDietPlan::create([
            'farmer_id' => (int) $data['farmer_id'],
            'animal_id' => (int) $data['animal_id'],
            'pan_id' => ! empty($data['pan_id']) ? (int) $data['pan_id'] : null,
            'diet_plan_name' => trim((string) $data['diet_plan_name']),
            'feed_type_id' => (int) $data['feed_type_id'],
            'reference_date' => $data['reference_date'],
            'body_weight' => round((float) $data['body_weight'], 2),
            'milk_production' => round((float) $data['milk_production'], 2),
            'target_dmi' => $targetDmi,
            'planned_dry_matter' => $plannedDryMatter,
            'dmi_gap' => round($plannedDryMatter - $targetDmi, 2),
            'days_count' => $request->filled('days_count') ? (int) $data['days_count'] : null,
            'plan_quantity' => $planQuantity,
            'consumed_quantity' => 0,
            'remaining_quantity' => $planQuantity,
            'unit' => trim((string) $data['unit']),
            'subtype_details' => $subtypes,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return back()->with('success', 'Diet plan added successfully.');
    }

    public function update(Request $request, FeedDietPlan $plan)
    {
        $data = $this->validatePayload($request);
        $subtypes = $this->extractSubtypePayload($request);

        if (empty($subtypes)) {
            return back()->withErrors([
                'subtype_details' => 'Please add at least one subtype with quantity and DM%.',
            ])->withInput();
        }

        $animal = Animal::query()
            ->where('id', (int) $data['animal_id'])
            ->where('farmer_id', (int) $data['farmer_id'])
            ->first();

        if (! $animal) {
            return back()->withErrors([
                'animal_id' => 'Selected animal is not valid for this farmer.',
            ])->withInput();
        }

        if (! empty($data['pan_id'])) {
            $pan = FarmerPan::query()
                ->where('id', (int) $data['pan_id'])
                ->where('farmer_id', (int) $data['farmer_id'])
                ->first();

            if (! $pan) {
                return back()->withErrors([
                    'pan_id' => 'Selected pen is not valid for this farmer.',
                ])->withInput();
            }
        }

        $planQuantity = round((float) collect($subtypes)->sum('quantity'), 2);
        $plannedDryMatter = round((float) collect($subtypes)->sum('dry_matter_quantity'), 2);
        $targetDmi = round((float) $data['target_dmi'], 2);

        $consumedQuantity = round((float) ($plan->consumed_quantity ?? 0), 2);

        $plan->update([
            'farmer_id' => (int) $data['farmer_id'],
            'animal_id' => (int) $data['animal_id'],
            'pan_id' => ! empty($data['pan_id']) ? (int) $data['pan_id'] : null,
            'diet_plan_name' => trim((string) $data['diet_plan_name']),
            'feed_type_id' => (int) $data['feed_type_id'],
            'reference_date' => $data['reference_date'],
            'body_weight' => round((float) $data['body_weight'], 2),
            'milk_production' => round((float) $data['milk_production'], 2),
            'target_dmi' => $targetDmi,
            'planned_dry_matter' => $plannedDryMatter,
            'dmi_gap' => round($plannedDryMatter - $targetDmi, 2),
            'days_count' => $request->filled('days_count') ? (int) $data['days_count'] : null,
            'plan_quantity' => $planQuantity,
            'remaining_quantity' => max(round($planQuantity - $consumedQuantity, 2), 0),
            'unit' => trim((string) $data['unit']),
            'subtype_details' => $subtypes,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return back()->with('success', 'Diet plan updated successfully.');
    }

    public function destroy(FeedDietPlan $plan)
    {
        $plan->delete();

        return back()->with('success', 'Diet plan deleted successfully.');
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'farmer_id' => ['required', 'exists:farmers,id'],
            'animal_id' => ['required', 'exists:animals,id'],
            'pan_id' => ['nullable', 'exists:farmer_pans,id'],
            'diet_plan_name' => ['required', 'string', 'max:255'],
            'feed_type_id' => ['required', 'exists:feed_types,id'],
            'reference_date' => ['required', 'date'],
            'body_weight' => ['required', 'numeric', 'min:0'],
            'milk_production' => ['required', 'numeric', 'min:0'],
            'target_dmi' => ['required', 'numeric', 'min:0'],
            'days_count' => ['nullable', 'integer', 'min:1', 'max:365'],
            'unit' => ['required', 'string', 'max:30'],
            'subtype_details_text' => ['nullable', 'string'],
            'subtype_details' => ['nullable', 'array'],
            'subtype_details.*.name' => ['nullable', 'string', 'max:255'],
            'subtype_details.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'subtype_details.*.dm_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    /**
     * @return array<int, array<string, float|string>>
     */
    private function parseSubtypeLines(string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($text)) ?: [];
        $rows = [];

        foreach ($lines as $line) {
            $value = trim((string) $line);
            if ($value === '') {
                continue;
            }

            $parts = array_map('trim', explode('|', $value));
            if (count($parts) < 3) {
                continue;
            }

            $name = trim((string) ($parts[0] ?? ''));
            $quantity = round((float) ($parts[1] ?? 0), 2);
            $dmPercent = round((float) ($parts[2] ?? 0), 2);

            if ($name === '' || $quantity <= 0 || $dmPercent <= 0 || $dmPercent > 100) {
                continue;
            }

            $rows[] = [
                'name' => $name,
                'quantity' => $quantity,
                'dm_percent' => $dmPercent,
                'dry_matter_quantity' => round(($quantity * $dmPercent) / 100, 2),
            ];
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, float|string>>
     */
    private function extractSubtypePayload(Request $request): array
    {
        $structured = collect((array) $request->input('subtype_details', []))
            ->map(function ($item) {
                $name = trim((string) data_get($item, 'name', ''));
                $quantity = round((float) data_get($item, 'quantity', 0), 2);
                $dmPercent = round((float) data_get($item, 'dm_percent', 0), 2);

                if ($name === '' || $quantity <= 0 || $dmPercent <= 0 || $dmPercent > 100) {
                    return null;
                }

                return [
                    'name' => $name,
                    'quantity' => $quantity,
                    'dm_percent' => $dmPercent,
                    'dry_matter_quantity' => round(($quantity * $dmPercent) / 100, 2),
                ];
            })
            ->filter()
            ->values()
            ->all();

        if (! empty($structured)) {
            return $structured;
        }

        return $this->parseSubtypeLines((string) $request->input('subtype_details_text', ''));
    }
}
