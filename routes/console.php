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
        ->whereDate('next_followup_date', $today)
        ->where(function ($query) use ($today) {
            $query->whereNull('followup_notified_on')
                ->orWhereDate('followup_notified_on', '!=', $today);
        })
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
            'event' => 'appointment_followup_due_today',
            'appointment_id' => (string) $appointment->id,
            'doctor_id' => (string) ($appointment->doctor_id ?? ''),
            'farmer_id' => (string) ($appointment->farmer_id ?? ''),
            'status' => (string) ($appointment->status ?? ''),
            'effective_status' => 'followup',
        ];

        $firebase->sendToDevice(
            optional($appointment->doctor)->fcm_token,
            'Follow-up Due Today',
            "Follow-up visit is due today for {$animalName}.",
            $payload
        );

        $firebase->sendToDevice(
            optional($appointment->farmer)->fcm_token,
            'Follow-up Due Today',
            "Dr. {$doctorName} follow-up is due today for {$animalName}.",
            $payload
        );

        $appointment->followup_notified_on = $today;
        $appointment->save();
        $count++;
    }

    Log::info('Follow-up reminders sent', ['count' => $count, 'date' => $today]);
})->name('appointments:followup-reminders')->everyTenMinutes()->withoutOverlapping();
