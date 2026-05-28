<?php

namespace App\Services;

use App\Models\Doctor\DoctorAppointment;
use App\Models\Farmer\Animal;
use App\Models\Farmer\AnimalPregnancy;
use App\Models\Farmer\Farmer;
use App\Models\Farmer\FarmerPan;
use App\Models\Farmer\FeedingRecord;
use App\Models\Farmer\MastitisRecord;
use App\Models\Farmer\MilkProduction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class FarmerReminderNotificationService
{
    public function __construct(protected FirebaseService $firebaseService)
    {
    }

    public function sendScheduledReminders(): void
    {
        foreach ([
            'milk_entry' => fn () => $this->sendMilkEntryReminders(),
            'feed_entry' => fn () => $this->sendFeedingEntryReminders(),
            'mastitis_check' => fn () => $this->sendMastitisCheckReminders(),
            'delivery_near' => fn () => $this->sendDeliveryNearReminders(),
            'doctor_rating' => fn () => $this->sendDoctorRatingReminders(),
        ] as $name => $callback) {
            try {
                $callback();
            } catch (Throwable $exception) {
                Log::warning('Farmer reminder notification skipped', [
                    'reminder' => $name,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }

    public function sendMilkTrendAlert(?Animal $animal, string $date, string $shift, float $currentQuantity): void
    {
        if (! $animal || $currentQuantity <= 0) {
            return;
        }

        $entryDate = Carbon::parse($date)->startOfDay();
        if (! $entryDate->isSameDay(now())) {
            return;
        }

        $column = $this->milkColumnForShift($shift);
        if ($column === null) {
            return;
        }

        $previous = MilkProduction::query()
            ->where('animal_id', $animal->id)
            ->whereDate('date', '<', $entryDate->toDateString())
            ->where($column, '>', 0)
            ->latest('date')
            ->latest('id')
            ->first();

        if (! $previous) {
            return;
        }

        $previousQuantity = (float) ($previous->{$column} ?? 0);
        if (abs($currentQuantity - $previousQuantity) < 0.01) {
            return;
        }

        $direction = $currentQuantity > $previousQuantity ? 'increase' : 'decrease';
        $animalName = $this->animalName($animal);
        $key = "farmer_reminder:milk_trend:{$animal->id}:{$entryDate->toDateString()}:{$shift}:{$direction}";

        if (! Cache::add($key, true, now()->addDays(2))) {
            return;
        }

        $this->sendToFarmer(
            $animal->farmer,
            "({$animalName}) Milk ".($direction === 'increase' ? 'Increase' : 'Decrease'),
            "{$animalName}'s {$shift} milk changed from {$this->formatNumber($previousQuantity)} L to {$this->formatNumber($currentQuantity)} L. Please check feed, health, or routine changes.",
            'milk_'.$direction,
            [
                'animal_id' => $animal->id,
                'shift' => $shift,
                'date' => $entryDate->toDateString(),
            ]
        );
    }

    protected function sendMilkEntryReminders(): void
    {
        foreach ($this->shiftReminderConfig('Milk') as $config) {
            if (! $this->shouldRunAt((int) $config['hour'])) {
                continue;
            }

            $shift = $config['shift'];
            $column = $this->milkColumnForShift($shift);
            if ($column === null) {
                continue;
            }

            foreach ($this->eligibleFarmers()->get() as $farmer) {
                if ($shift === 'Afternoon' && ! $this->farmerHasAfternoonShift((int) $farmer->id)) {
                    continue;
                }

                $hasEntry = MilkProduction::query()
                    ->whereHas('animal', fn ($query) => $query->where('farmer_id', $farmer->id))
                    ->whereDate('date', today()->toDateString())
                    ->where($column, '>', 0)
                    ->exists();

                if ($hasEntry) {
                    continue;
                }

                $this->sendDailyReminder(
                    $farmer,
                    "Please Enter Today's {$shift} Milk",
                    "Today's {$shift} milk entry is pending.",
                    'milk_entry_'.$shift
                );
            }
        }
    }

    protected function sendFeedingEntryReminders(): void
    {
        foreach ($this->shiftReminderConfig('Feed') as $config) {
            if (! $this->shouldRunAt((int) $config['hour'])) {
                continue;
            }

            $shift = $config['shift'];

            foreach ($this->eligibleFarmers()->get() as $farmer) {
                if ($shift === 'Afternoon' && ! $this->farmerHasAfternoonShift((int) $farmer->id)) {
                    continue;
                }

                $hasEntry = FeedingRecord::query()
                    ->where('farmer_id', $farmer->id)
                    ->whereDate('date', today()->toDateString())
                    ->where('feeding_time', $shift)
                    ->exists();

                if ($hasEntry) {
                    continue;
                }

                $this->sendDailyReminder(
                    $farmer,
                    "Please Enter Today's {$shift} Feed",
                    "Today's {$shift} feed entry is pending.",
                    'feed_entry_'.$shift
                );
            }
        }
    }

    protected function sendMastitisCheckReminders(): void
    {
        foreach ($this->eligibleFarmers()->get() as $farmer) {
            $latestDate = MastitisRecord::query()
                ->where('farmer_id', $farmer->id)
                ->latest('date')
                ->value('date');

            $basisDate = $latestDate
                ? Carbon::parse($latestDate)
                : ($farmer->created_at ? Carbon::parse($farmer->created_at) : null);
            if (! $basisDate || $basisDate->gt(now()->subMonth())) {
                continue;
            }

            $key = "farmer_reminder:mastitis_check:{$farmer->id}";
            if (! Cache::add($key, true, now()->addDays(10))) {
                continue;
            }

            $this->sendToFarmer(
                $farmer,
                'Please Check Mastitis Test',
                'It has been one month since the last mastitis test. Please check mastitis status for your herd.',
                'mastitis_check_due'
            );
        }
    }

    protected function sendDeliveryNearReminders(): void
    {
        $targetDate = now()->addDays(10)->toDateString();

        $records = AnimalPregnancy::query()
            ->with(['animal.farmer'])
            ->where('status', 'pregnant')
            ->whereNull('calving_date')
            ->whereDate('expected_calving_date', $targetDate)
            ->get();

        foreach ($records as $record) {
            $animal = $record->animal;
            if (! $animal) {
                continue;
            }

            $key = "farmer_reminder:delivery_near:{$record->id}:{$targetDate}";
            if (! Cache::add($key, true, now()->addDays(15))) {
                continue;
            }

            $animalName = $this->animalName($animal);
            $this->sendToFarmer(
                $animal->farmer,
                "Your ({$animalName}) Delivery Is Near.",
                "{$animalName}'s tentative calving date is {$targetDate}. Please keep arrangements ready.",
                'delivery_near',
                [
                    'animal_id' => $animal->id,
                    'pregnancy_id' => $record->id,
                    'expected_calving_date' => $targetDate,
                ]
            );
        }
    }

    protected function sendDoctorRatingReminders(): void
    {
        $appointments = DoctorAppointment::query()
            ->with(['doctor', 'farmer', 'rating'])
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', now()->subDays(2))
            ->whereDoesntHave('rating')
            ->get();

        foreach ($appointments as $appointment) {
            $key = "farmer_reminder:doctor_rating:{$appointment->id}";
            if (! Cache::add($key, true, now()->addDays(7))) {
                continue;
            }

            $animalName = trim((string) ($appointment->animal_name ?: 'animal'));
            $doctorName = optional($appointment->doctor)->full_name ?: optional($appointment->doctor)->name ?: 'doctor';

            $this->sendToFarmer(
                $appointment->farmer,
                "Please Rate Doctor To {$animalName}'s Treatment",
                "Treatment is complete. Please rate Dr. {$doctorName} for {$animalName}'s treatment.",
                'doctor_rating_due',
                [
                    'appointment_id' => $appointment->id,
                    'doctor_id' => $appointment->doctor_id,
                ]
            );
        }
    }

    protected function sendDailyReminder(Farmer $farmer, string $title, string $body, string $event): void
    {
        $key = 'farmer_reminder:'.$event.':'.$farmer->id.':'.today()->toDateString();
        if (! Cache::add($key, true, now()->addDay())) {
            return;
        }

        $this->sendToFarmer($farmer, $title, $body, $event);
    }

    protected function sendToFarmer(?Farmer $farmer, string $title, string $body, string $event, array $data = []): bool
    {
        if (! $farmer || blank($farmer->fcm_token)) {
            return false;
        }

        return $this->firebaseService->sendToDevice(
            $farmer->fcm_token,
            $title,
            $body,
            array_merge([
                'type' => 'farmer_reminder',
                'event' => $event,
                'farmer_id' => (string) $farmer->id,
            ], $data)
        );
    }

    protected function eligibleFarmers()
    {
        return Farmer::query()
            ->where('is_active', true)
            ->whereNotNull('fcm_token')
            ->where('fcm_token', '<>', '')
            ->whereHas('animals', function ($query) {
                $query->where('is_active', true)
                    ->whereNotIn('lifecycle_status', ['sold', 'death']);
            });
    }

    protected function farmerHasAfternoonShift(int $farmerId): bool
    {
        return FarmerPan::query()
            ->where('farmer_id', $farmerId)
            ->where('pan_type', 'milking')
            ->get()
            ->contains(function (FarmerPan $pan) {
                $shifts = is_array($pan->milk_shifts) ? $pan->milk_shifts : [];
                return collect($shifts)
                    ->map(fn ($shift) => strtolower(trim((string) $shift)))
                    ->contains('afternoon');
            });
    }

    protected function shiftReminderConfig(string $type): array
    {
        return [
            ['shift' => 'Morning', 'hour' => 11, 'type' => $type],
            ['shift' => 'Afternoon', 'hour' => 15, 'type' => $type],
            ['shift' => 'Evening', 'hour' => 21, 'type' => $type],
        ];
    }

    protected function shouldRunAt(int $hour): bool
    {
        $now = now();
        $start = $now->copy()->setTime($hour, 0, 0);
        $end = $start->copy()->addMinutes(14)->endOfMinute();

        return $now->gte($start) && $now->lte($end);
    }

    protected function milkColumnForShift(string $shift): ?string
    {
        return match ($shift) {
            'Morning' => 'morning_milk',
            'Afternoon' => 'afternoon_milk',
            'Evening' => 'evening_milk',
            default => null,
        };
    }

    protected function animalName(Animal $animal): string
    {
        $name = trim((string) ($animal->animal_name ?: 'Animal'));
        return $name === '' ? 'Animal' : $name;
    }

    protected function formatNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
