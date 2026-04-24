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

        $seed = $groupRows->first();
        $requestedAt = $seed->requested_at ?: $seed->created_at;
        if (! $requestedAt) {
            continue;
        }

        $elapsedMinutes = max(0, (int) floor($requestedAt->diffInSeconds($now) / 60));
        $allowedMaxRadius = min(20, 5 + ($elapsedMinutes * 5));
        if ($allowedMaxRadius <= 5) {
            continue;
        }

        $toNotify = $groupRows
            ->where('status', 'pending')
            ->whereNull('notified_at')
            ->filter(function (DoctorAppointment $row) use ($allowedMaxRadius) {
                $doctor = $row->doctor;
                if (! $doctor || (string) ($doctor->status ?? '') !== 'approved') {
                    return false;
                }
                if (! (bool) ($doctor->is_active_for_appointments ?? false)) {
                    return false;
                }

                return (int) ($row->notify_radius_to_km ?? 0) <= $allowedMaxRadius;
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
                    'radius_to_km' => (string) ($row->notify_radius_to_km ?? 0),
                ]
            );

            if ($sent) {
                $row->notified_at = now();
                $row->save();
            }
        }
    }
})->name('appointments:radius-escalation')->everyMinute()->withoutOverlapping();
