<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Dairy\Dairy;
use App\Models\Farmer\Animal;
use App\Models\Farmer\Farmer;
use App\Models\Farmer\FarmerPan;
use App\Models\Farmer\MilkProduction;
use App\Models\Farmer\PanMilkEntry;
use App\Services\FarmerReminderNotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MilkProduceListController extends Controller
{
    public function __construct(protected FarmerReminderNotificationService $reminderNotifications)
    {
    }

    public function index()
    {
        $milkProductions = MilkProduction::with(['animal.farmer', 'dairy'])->latest()->get();

        $summary = [
            'morning' => MilkProduction::sum('morning_milk'),
            'afternoon' => MilkProduction::sum('afternoon_milk'),
            'evening' => MilkProduction::sum('evening_milk'),
            'fat' => MilkProduction::avg('fat'),
        ];

        return view('milk_production.index', compact('milkProductions', 'summary'));
    }

    public function create()
    {
        return view('milk_production.create', $this->getCreateViewData());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'farmer_id' => ['required', 'exists:farmers,id'],
            'animal_id' => ['nullable', 'exists:animals,id'],
            'pan_id' => ['nullable', 'exists:farmer_pans,id'],
            'dairy_id' => ['nullable', 'exists:dairies,id'],
            'date' => ['required', 'date'],
            'shift' => ['required', 'in:Morning,Afternoon,Evening'],
            'quantity_liters' => ['required', 'numeric', 'min:0.1'],
            'fat' => ['required', 'numeric', 'min:0'],
            'snf' => ['required', 'numeric', 'min:0'],
            'rate' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'cow_milk_details' => ['nullable', 'array'],
            'cow_milk_details.*.animal_id' => ['nullable', 'integer', 'exists:animals,id'],
            'cow_milk_details.*.final_milk_qty' => ['nullable', 'numeric', 'min:0'],
        ]);

        $dateObject = Carbon::parse($data['date'])->startOfDay();
        if ($dateObject->gt(now()->startOfDay())) {
            return back()->withErrors([
                'date' => 'Milk date cannot be a future date.',
            ])->withInput();
        }

        $farmerId = (int) $data['farmer_id'];
        $animalId = ! empty($data['animal_id']) ? (int) $data['animal_id'] : 0;
        $panId = ! empty($data['pan_id']) ? (int) $data['pan_id'] : 0;

        if ($animalId === 0 && $panId === 0) {
            return back()->withErrors([
                'animal_id' => 'Please select one animal or one Pen.',
            ])->withInput();
        }

        if ($animalId > 0 && $panId > 0) {
            return back()->withErrors([
                'animal_id' => 'Please select either animal or Pen, not both.',
            ])->withInput();
        }

        if ($panId > 0) {
            return $this->storePanEntry($data, $dateObject);
        }

        return $this->storeAnimalEntry($data, $dateObject);
    }

    private function storeAnimalEntry(array $data, Carbon $dateObject)
    {
        $animal = Animal::query()
            ->with('farmer')
            ->where('id', (int) $data['animal_id'])
            ->where('farmer_id', (int) $data['farmer_id'])
            ->first();

        if (! $animal) {
            return back()->withErrors([
                'animal_id' => 'Selected animal is not valid for this farmer.',
            ])->withInput();
        }

        $date = $dateObject->toDateString();
        $shift = (string) $data['shift'];
        $quantity = round((float) $data['quantity_liters'], 2);

        if ($this->shiftEntryExists((int) $animal->id, $date, $shift)) {
            return back()->withErrors([
                'shift' => 'Milk entry already exists for the selected animal, date, and shift.',
            ])->withInput();
        }

        $milk = MilkProduction::create([
            'animal_id' => $animal->id,
            'dairy_id' => ! empty($data['dairy_id']) ? (int) $data['dairy_id'] : null,
            'date' => $date,
            'fat' => round((float) $data['fat'], 2),
            'snf' => round((float) $data['snf'], 2),
            'rate' => round((float) $data['rate'], 2),
            ...$this->shiftValues($shift, $quantity),
        ]);

        $this->reminderNotifications->sendMilkTrendAlert(
            $animal,
            $date,
            $shift,
            $quantity
        );

        return redirect()
            ->route('farmer.milk')
            ->with('success', 'Milk entry added successfully.');
    }

    private function storePanEntry(array $data, Carbon $dateObject)
    {
        $farmerId = (int) $data['farmer_id'];
        $pan = FarmerPan::query()
            ->where('id', (int) $data['pan_id'])
            ->where('farmer_id', $farmerId)
            ->first();

        if (! $pan) {
            return back()->withErrors([
                'pan_id' => 'Selected Pen is not valid for this farmer.',
            ])->withInput();
        }

        $details = collect($data['cow_milk_details'] ?? [])
            ->map(function ($item) {
                return [
                    'animal_id' => (int) ($item['animal_id'] ?? 0),
                    'final_milk_qty' => round((float) ($item['final_milk_qty'] ?? 0), 2),
                ];
            })
            ->filter(fn ($item) => $item['animal_id'] > 0)
            ->values();

        if ($details->isEmpty()) {
            return back()->withErrors([
                'cow_milk_details' => 'Please enter cow-wise milk distribution for the selected Pen.',
            ])->withInput();
        }

        if ($details->pluck('animal_id')->duplicates()->isNotEmpty()) {
            return back()->withErrors([
                'cow_milk_details' => 'Duplicate cow rows are not allowed.',
            ])->withInput();
        }

        $animals = Animal::query()
            ->with('farmer')
            ->where('farmer_id', $farmerId)
            ->where('pan_id', $pan->id)
            ->whereIn('id', $details->pluck('animal_id')->all())
            ->get()
            ->keyBy('id');

        if ($animals->count() !== $details->count()) {
            return back()->withErrors([
                'cow_milk_details' => 'Every cow milk row must belong to the selected Pen.',
            ])->withInput();
        }

        $panAnimalCount = Animal::query()
            ->where('farmer_id', $farmerId)
            ->where('pan_id', $pan->id)
            ->count();

        if ($panAnimalCount !== $details->count()) {
            return back()->withErrors([
                'cow_milk_details' => 'Please submit milk quantity for every cow in this Pen.',
            ])->withInput();
        }

        $quantityLiters = round((float) $data['quantity_liters'], 2);
        $cowTotal = round((float) $details->sum('final_milk_qty'), 2);
        if (abs($cowTotal - $quantityLiters) > 0.01) {
            return back()->withErrors([
                'quantity_liters' => 'Cow-wise total does not match quantity liters.',
            ])->withInput();
        }

        $date = $dateObject->toDateString();
        $shift = (string) $data['shift'];

        $existingShiftEntries = MilkProduction::query()
            ->whereIn('animal_id', $details->pluck('animal_id')->all())
            ->whereDate('date', $date)
            ->where($this->shiftColumn($shift), '>', 0)
            ->pluck('animal_id')
            ->unique()
            ->values();

        if ($existingShiftEntries->isNotEmpty()) {
            $duplicateNames = $details
                ->whereIn('animal_id', $existingShiftEntries->all())
                ->map(function (array $detail) use ($animals) {
                    $animal = $animals->get($detail['animal_id']);
                    $name = trim((string) ($animal?->animal_name ?? 'Animal'));
                    $tag = trim((string) ($animal?->tag_number ?? ''));

                    return $tag === '' ? $name : "{$name} ({$tag})";
                })
                ->values()
                ->all();

            return back()->withErrors([
                'shift' => 'Milk entry already exists for the selected Pen cows on this date and shift: '.implode(', ', $duplicateNames),
            ])->withInput();
        }

        DB::transaction(function () use ($data, $date, $shift, $farmerId, $pan, $quantityLiters, $cowTotal, $details, $animals) {
            $panMilkEntry = PanMilkEntry::create([
                'farmer_id' => $farmerId,
                'pan_id' => $pan->id,
                'dairy_id' => ! empty($data['dairy_id']) ? (int) $data['dairy_id'] : null,
                'date' => $date,
                'shift' => $shift,
                'quantity_liters' => $quantityLiters,
                'cow_total_liters' => $cowTotal,
                'fat' => round((float) $data['fat'], 2),
                'snf' => round((float) $data['snf'], 2),
                'rate' => round((float) $data['rate'], 2),
                'notes' => trim((string) ($data['notes'] ?? '')),
            ]);

            foreach ($details as $detail) {
                $quantity = (float) $detail['final_milk_qty'];

                $milk = MilkProduction::create([
                    'animal_id' => $detail['animal_id'],
                    'dairy_id' => ! empty($data['dairy_id']) ? (int) $data['dairy_id'] : null,
                    'date' => $date,
                    'fat' => round((float) $data['fat'], 2),
                    'snf' => round((float) $data['snf'], 2),
                    'rate' => round((float) $data['rate'], 2),
                    ...$this->shiftValues($shift, $quantity),
                ]);

                $animal = $animals->get($detail['animal_id']);
                $panMilkEntry->details()->create([
                    'animal_id' => $detail['animal_id'],
                    'milk_production_id' => $milk->id,
                    'default_milk_per_session' => $animal?->default_milk_per_session,
                    'final_milk_qty' => $quantity,
                ]);
            }
        });

        foreach ($animals as $animal) {
            $matchedDetail = $details->firstWhere('animal_id', (int) $animal->id);
            if (! $matchedDetail) {
                continue;
            }

            $this->reminderNotifications->sendMilkTrendAlert(
                $animal,
                $date,
                $shift,
                (float) $matchedDetail['final_milk_qty']
            );
        }

        return redirect()
            ->route('farmer.milk')
            ->with('success', 'Pen milk entry added successfully.');
    }

    private function getCreateViewData(): array
    {
        $farmers = Farmer::query()->orderBy('first_name')->orderBy('last_name')->get();
        $animals = Animal::query()
            ->with(['farmer', 'pan', 'animalType'])
            ->orderBy('animal_name')
            ->get()
            ->filter(fn (Animal $animal) => $this->isMilkingAnimalTypeName((string) optional($animal->animalType)->name))
            ->values();
        $pans = FarmerPan::query()
            ->with(['farmer', 'animals.animalType'])
            ->orderBy('name')
            ->get()
            ->filter(function (FarmerPan $pan) {
                return $pan->animals->contains(fn (Animal $animal) => $this->isMilkingAnimalTypeName((string) optional($animal->animalType)->name));
            })
            ->values();
        $dairies = Dairy::query()
            ->with('farmer')
            ->orderBy('dairy_name')
            ->get();

        $pansData = $pans->mapWithKeys(function (FarmerPan $pan) {
            $cows = $pan->animals
                ->filter(fn (Animal $animal) => $this->isMilkingAnimalTypeName((string) optional($animal->animalType)->name))
                ->map(function (Animal $animal) {
                    return [
                        'id' => (int) $animal->id,
                        'name' => $animal->animal_name,
                        'tag_number' => $animal->tag_number,
                        'default_milk_per_session' => round((float) ($animal->default_milk_per_session ?? 0), 2),
                    ];
                })
                ->values()
                ->all();

            return [
                $pan->id => [
                    'id' => (int) $pan->id,
                    'name' => $pan->name,
                    'farmer_id' => (int) $pan->farmer_id,
                    'milk_shifts' => array_values((array) ($pan->milk_shifts ?? [])),
                    'cows' => $cows,
                ],
            ];
        })->all();

        return compact('farmers', 'animals', 'pans', 'dairies', 'pansData');
    }

    private function shiftEntryExists(int $animalId, string $date, string $shift, ?int $exceptId = null): bool
    {
        $query = MilkProduction::query()
            ->where('animal_id', $animalId)
            ->whereDate('date', $date)
            ->where($this->shiftColumn($shift), '>', 0);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->exists();
    }

    private function shiftValues(string $shift, float $quantity): array
    {
        return [
            'morning_milk' => $shift === 'Morning' ? $quantity : 0,
            'afternoon_milk' => $shift === 'Afternoon' ? $quantity : 0,
            'evening_milk' => $shift === 'Evening' ? $quantity : 0,
        ];
    }

    private function shiftColumn(string $shift): string
    {
        return match ($shift) {
            'Morning' => 'morning_milk',
            'Afternoon' => 'afternoon_milk',
            'Evening' => 'evening_milk',
            default => throw new \InvalidArgumentException("Unsupported milk shift [{$shift}]."),
        };
    }

    private function isMilkingAnimalTypeName(string $typeName): bool
    {
        $value = trim(strtolower($typeName));
        if ($value === '') {
            return true;
        }

        return str_contains($value, 'milking')
            || str_contains($value, 'milky')
            || str_contains($value, 'dudh')
            || str_contains($value, 'dugh')
            || str_contains($value, 'milk');
    }
}
