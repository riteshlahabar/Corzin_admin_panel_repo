<?php

use App\Models\Doctor\DoctorAppointment;
use App\Services\FirebaseService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function (): void {
    $today = now()->toDateString();

    $appointments = DoctorAppointment::query()
        ->with(['doctor', 'farmer'])
        ->where('status', 'completed')
        ->where('followup_required', true)
        ->whereDate('next_followup_date', '<=', $today)
        ->whereNull('followup_notified_on')
        ->get();

    if ($appointments->isEmpty()) {
        return;
    }

    $firebase = app(FirebaseService::class);
    $count = 0;
    foreach ($appointments as $appointment) {
        $doctorName = optional($appointment->doctor)->full_name ?: 'Doctor';
        $animalName = $appointment->animal_name ?: 'animal';

        $payload = [
            'type' => 'doctor_appointment',
            'event' => 'appointment_followup_due',
            'appointment_id' => (string) $appointment->id,
            'doctor_id' => (string) ($appointment->doctor_id ?? ''),
            'farmer_id' => (string) ($appointment->farmer_id ?? ''),
            'status' => (string) ($appointment->status ?? ''),
            'effective_status' => 'followup',
        ];

        $firebase->sendToDevice(
            optional($appointment->farmer)->fcm_token,
            'Follow-up Reminder',
            "It has been 5 days since treatment by Dr. {$doctorName} for {$animalName}.",
            $payload
        );

        $appointment->followup_notified_on = $today;
        $appointment->save();
        $count++;
    }

    Log::info('Follow-up reminders sent', ['count' => $count, 'date' => $today]);
})->name('appointments:followup-reminders')->everyTenMinutes()->withoutOverlapping();

Schedule::call(function (): void {
    $now = now();
    $firebase = app(FirebaseService::class);
    $maxRoutingRadiusKm = 30;

    $groupIds = DoctorAppointment::query()
        ->whereNotNull('appointment_group_id')
        ->where('status', 'pending')
        ->whereNull('notified_at')
        ->distinct()
        ->pluck('appointment_group_id');

    foreach ($groupIds as $groupId) {
        $groupRows = DoctorAppointment::query()
            ->with(['doctor', 'farmer'])
            ->where('appointment_group_id', $groupId)
            ->orderBy('id')
            ->get();

        if ($groupRows->isEmpty()) {
            continue;
        }

        $hasAccepted = $groupRows->contains(function (DoctorAppointment $row) {
            return in_array(strtolower((string) $row->status), ['approved', 'scheduled', 'in_progress', 'completed'], true);
        });
        if ($hasAccepted) {
            continue;
        }

        $pendingRows = $groupRows
            ->filter(fn (DoctorAppointment $row) => strtolower((string) $row->status) === 'pending')
            ->filter(fn (DoctorAppointment $row) => (int) ($row->notify_radius_to_km ?? 0) > 0)
            ->filter(fn (DoctorAppointment $row) => (int) ($row->notify_radius_to_km ?? 0) <= $maxRoutingRadiusKm)
            ->values();

        if ($pendingRows->isEmpty()) {
            continue;
        }

        $notifiedPendingRows = $pendingRows
            ->filter(fn (DoctorAppointment $row) => $row->notified_at !== null)
            ->values();

        $nextRadiusToNotify = null;
        if ($notifiedPendingRows->isEmpty()) {
            // No wave reached any doctor yet:
            // notify the nearest available band immediately.
            $nextRadiusToNotify = $pendingRows
                ->filter(fn (DoctorAppointment $row) => $row->notified_at === null)
                ->map(fn (DoctorAppointment $row) => (int) ($row->notify_radius_to_km ?? 0))
                ->sort()
                ->first();
        } else {
            $lastReachedRadius = (int) $notifiedPendingRows
                ->map(fn (DoctorAppointment $row) => (int) ($row->notify_radius_to_km ?? 0))
                ->max();

            $lastReachedTimestamp = (int) $notifiedPendingRows
                ->filter(fn (DoctorAppointment $row) => (int) ($row->notify_radius_to_km ?? 0) === $lastReachedRadius)
                ->map(fn (DoctorAppointment $row) => optional($row->notified_at)->timestamp ?? 0)
                ->max();

            if ($lastReachedTimestamp <= 0) {
                continue;
            }

            $readyAt = \Illuminate\Support\Carbon::createFromTimestamp($lastReachedTimestamp)->addMinute();
            if ($now->lt($readyAt)) {
                continue;
            }

            // Wait only after a wave has actually reached doctors.
            // If intermediate bands are empty, jump to the next existing one.
            $nextRadiusToNotify = $pendingRows
                ->filter(fn (DoctorAppointment $row) => $row->notified_at === null)
                ->map(fn (DoctorAppointment $row) => (int) ($row->notify_radius_to_km ?? 0))
                ->filter(fn (int $radiusTo) => $radiusTo > $lastReachedRadius)
                ->sort()
                ->first();
        }

        if (! $nextRadiusToNotify) {
            continue;
        }

        $toNotify = $pendingRows
            ->where('status', 'pending')
            ->whereNull('notified_at')
            ->filter(fn (DoctorAppointment $row) => (int) ($row->notify_radius_to_km ?? 0) === (int) $nextRadiusToNotify)
            ->filter(function (DoctorAppointment $row) {
                $doctor = $row->doctor;
                if (! $doctor || (string) ($doctor->status ?? '') !== 'approved') {
                    return false;
                }
                if (! (bool) ($doctor->is_active_for_appointments ?? false)) {
                    return false;
                }
                if (blank($doctor->fcm_token ?? null)) {
                    return false;
                }

                return true;
            })
            ->values();

        foreach ($toNotify as $row) {
            $row->refresh()->loadMissing(['doctor', 'farmer']);
            $sent = $firebase->sendToDevice(
                optional($row->doctor)->fcm_token,
                'New Appointment Request',
                trim(($row->farmer_name ?? 'Farmer').' requested a visit for '.($row->animal_name ?? 'animal')),
                [
                    'type' => 'doctor_appointment',
                    'event' => 'appointment_created',
                    'appointment_id' => (string) $row->id,
                    'doctor_id' => (string) ($row->doctor_id ?? ''),
                    'status' => (string) ($row->status ?? ''),
                    'radius_from_km' => (string) ($row->notify_radius_from_km ?? 0),
                    'radius_to_km' => (string) ($row->notify_radius_to_km ?? $nextRadiusToNotify),
                ]
            );

            if ($sent) {
                $row->notified_at = now();
                $row->save();
            }
        }
    }
})->name('appointments:radius-escalation')->everyMinute()->withoutOverlapping();
