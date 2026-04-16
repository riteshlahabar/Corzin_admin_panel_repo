<?php

namespace App\Http\Controllers\Api\DoctorApp;

use App\Http\Controllers\Controller;
use App\Models\Doctor\DoctorAdminNotification;
use App\Models\Doctor\Doctor;
use App\Models\Doctor\DoctorAppointment;
use App\Models\Doctor\DoctorDisease;
use App\Models\Farmer\Animal;
use App\Models\Farmer\Farmer;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DoctorAppointmentController extends Controller
{
    public function __construct(protected FirebaseService $firebaseService)
    {
    }

    public function indexByDoctor(Doctor $doctor)
    {
        $appointments = DoctorAppointment::query()
            ->where('doctor_id', $doctor->id)
            ->with(['doctor', 'farmer'])
            ->latest('requested_at')
            ->latest()
            ->get()
            ->map(fn (DoctorAppointment $appointment) => $this->appointmentPayload($appointment, false));

        return response()->json([
            'status' => true,
            'message' => 'Doctor appointments fetched successfully.',
            'data' => $appointments,
        ]);
    }

    public function indexByFarmer(Farmer $farmer)
    {
        $rows = DoctorAppointment::query()
            ->where(function ($query) use ($farmer) {
                $query->where('farmer_id', $farmer->id);
                if (! blank($farmer->mobile)) {
                    $query->orWhere('farmer_phone', $farmer->mobile);
                }
            })
            ->with(['doctor', 'farmer'])
            ->latest('requested_at')
            ->latest()
            ->get();

        // Farmer app should receive one card per appointment request group.
        $grouped = $rows
            ->groupBy(function (DoctorAppointment $appointment) {
                return blank($appointment->appointment_group_id)
                    ? 'id_'.$appointment->id
                    : (string) $appointment->appointment_group_id;
            })
            ->map(function ($items) {
                $items = $items->sort(function (DoctorAppointment $a, DoctorAppointment $b) {
                    $rankDiff = $this->farmerStatusRank($b) <=> $this->farmerStatusRank($a);
                    if ($rankDiff !== 0) {
                        return $rankDiff;
                    }

                    $aTime = optional($a->requested_at)->timestamp ?? optional($a->created_at)->timestamp ?? 0;
                    $bTime = optional($b->requested_at)->timestamp ?? optional($b->created_at)->timestamp ?? 0;
                    return $bTime <=> $aTime;
                });

                return $items->first();
            })
            ->values()
            ->map(fn (DoctorAppointment $appointment) => $this->appointmentPayload($appointment, true));

        return response()->json([
            'status' => true,
            'message' => 'Farmer appointments fetched successfully.',
            'data' => $grouped,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'doctor_id' => ['required', 'exists:doctors,id'],
            'farmer_id' => ['nullable', 'exists:farmers,id'],
            'animal_id' => ['nullable', 'exists:animals,id'],
            'farmer_name' => ['nullable', 'string', 'max:255'],
            'farmer_phone' => ['nullable', 'string', 'max:30'],
            'animal_name' => ['nullable', 'string', 'max:255'],
            'concern' => ['required', 'string'],
            'disease_ids' => ['nullable', 'array'],
            'disease_ids.*' => ['integer', 'exists:doctor_diseases,id'],
            'disease_details' => ['nullable', 'string'],
            'requested_at' => ['nullable', 'date'],
            'address' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'notes' => ['nullable', 'string'],
            'animal_photo' => ['nullable', 'string', 'max:255'],
            'animal_photo_file' => ['nullable', 'image', 'max:5120'],
        ]);

        $farmer = null;
        if (! empty($data['farmer_id'])) {
            $farmer = Farmer::find($data['farmer_id']);
        }

        $animal = null;
        if (! empty($data['animal_id'])) {
            $animal = Animal::find($data['animal_id']);
        }

        $animalPhoto = $data['animal_photo'] ?? null;
        if ($request->hasFile('animal_photo_file')) {
            $animalPhoto = $this->storeAnimalPhoto($request->file('animal_photo_file'));
        } elseif ($animal && ! empty($animal->image)) {
            $animalPhoto = $animal->image;
        }

        $appointment = DoctorAppointment::create([
            'doctor_id' => (int) $data['doctor_id'],
            'appointment_group_id' => (string) Str::uuid(),
            'farmer_id' => $data['farmer_id'] ?? null,
            'animal_id' => $data['animal_id'] ?? null,
            'farmer_name' => $data['farmer_name']
                ?? ($farmer ? trim(($farmer->first_name ?? '').' '.($farmer->last_name ?? '')) : null),
            'farmer_phone' => $data['farmer_phone'] ?? ($farmer->mobile ?? null),
            'animal_name' => $data['animal_name'] ?? ($animal->animal_name ?? null),
            'animal_photo' => $animalPhoto,
            'concern' => $data['concern'],
            'disease_ids' => $data['disease_ids'] ?? [],
            'disease_details' => $data['disease_details'] ?? null,
            'status' => 'pending',
            'requested_at' => $data['requested_at'] ?? now(),
            'address' => $data['address'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        $appointment->loadMissing(['doctor', 'farmer']);
        $this->notifyDoctor(
            $appointment,
            'New Appointment Request',
            trim(($appointment->farmer_name ?? 'Farmer').' requested a visit for '.($appointment->animal_name ?? 'animal')),
            ['event' => 'appointment_created']
        );
        $this->notifyWebAdmin(
            $appointment,
            'appointment_created',
            'New appointment request',
            trim(($appointment->farmer_name ?? 'Farmer').' created appointment #'.$appointment->id)
        );

        return response()->json([
            'status' => true,
            'message' => 'Appointment request created successfully.',
            'data' => $this->appointmentPayload($appointment, true),
        ], 201);
    }

    public function propose(Request $request, DoctorAppointment $appointment)
    {
        $data = $request->validate([
            'scheduled_at' => ['required', 'date'],
            'charges' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $appointment->update([
            'scheduled_at' => $data['scheduled_at'],
            'charges' => $data['charges'],
            'status' => 'proposed',
            'notes' => $data['notes'] ?? $appointment->notes,
        ]);

        $appointment->refresh()->loadMissing(['doctor', 'farmer']);
        $this->notifyFarmer(
            $appointment,
            'Doctor Shared Slot',
            'Your appointment has a proposed time and charge. Please review and approve.',
            ['event' => 'appointment_proposed']
        );
        $this->notifyWebAdmin(
            $appointment,
            'appointment_proposed',
            'Doctor proposed appointment slot',
            'Appointment #'.$appointment->id.' moved to proposed with schedule and charges.'
        );

        return response()->json([
            'status' => true,
            'message' => 'Appointment slot and charges shared with farmer.',
            'data' => $this->appointmentPayload($appointment, false),
        ]);
    }

    public function complete(Request $request, DoctorAppointment $appointment)
    {
        $data = $request->validate([
            'notes' => ['nullable', 'string'],
            'treatment_details' => ['nullable', 'string'],
            'onsite_treatment' => ['nullable', 'string'],
            'followup_required' => ['nullable', 'boolean'],
            'next_followup_date' => ['nullable', 'date'],
        ]);

        $appointment->update([
            'status' => 'completed',
            'completed_at' => now(),
            'notes' => $data['notes'] ?? $appointment->notes,
            'treatment_details' => $data['treatment_details'] ?? $appointment->treatment_details,
            'onsite_treatment' => $data['onsite_treatment']
                ?? $this->extractOnsiteTreatment($data['treatment_details'] ?? $appointment->treatment_details)
                ?? $appointment->onsite_treatment,
            'followup_required' => $data['followup_required'] ?? $appointment->followup_required,
            'next_followup_date' => $data['next_followup_date'] ?? $appointment->next_followup_date,
        ]);

        $appointment->refresh()->loadMissing(['doctor', 'farmer']);
        $this->notifyFarmer(
            $appointment,
            'Treatment Completed',
            'Doctor marked your appointment as completed.',
            ['event' => 'appointment_completed']
        );
        $this->notifyWebAdmin(
            $appointment,
            'appointment_completed',
            'Appointment completed',
            'Appointment #'.$appointment->id.' has been marked completed by doctor.'
        );

        return response()->json([
            'status' => true,
            'message' => 'Appointment marked as completed.',
            'data' => $this->appointmentPayload($appointment, true),
        ]);
    }

    public function doctorDecision(Request $request, DoctorAppointment $appointment)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['approved', 'declined', 'rescheduled'])],
            'scheduled_at' => ['nullable', 'date'],
            'charges' => ['nullable', 'numeric', 'min:0'],
        ]);

        $action = $data['action'];

        if ($action === 'rescheduled') {
            if (empty($data['scheduled_at']) || ! isset($data['charges'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'scheduled_at and charges are required for reschedule.',
                ], 422);
            }

            $appointment->update([
                'status' => 'rescheduled',
                'scheduled_at' => $data['scheduled_at'],
                'charges' => $data['charges'],
            ]);
        } elseif ($action === 'approved') {
            $otpCode = (string) random_int(100000, 999999);
            $appointment->update([
                'status' => 'approved',
                'farmer_approved_at' => now(),
                'otp_code' => $otpCode,
                'otp_verified_at' => null,
            ]);
        } else {
            $appointment->update([
                'status' => 'declined',
            ]);
        }

        $appointment->refresh()->loadMissing(['doctor', 'farmer']);
        if ($action === 'approved') {
            $this->notifyFarmer(
                $appointment,
                'Appointment Approved',
                'Doctor approved your visit. Share this OTP at visit time: '.($appointment->otp_code ?? ''),
                ['event' => 'appointment_doctor_approved']
            );
        } elseif ($action === 'rescheduled') {
            $this->notifyFarmer(
                $appointment,
                'Appointment Rescheduled',
                'Doctor proposed a new appointment slot.',
                ['event' => 'appointment_rescheduled']
            );
        } else {
            $this->notifyFarmer(
                $appointment,
                'Appointment Declined',
                'Doctor declined this appointment request.',
                ['event' => 'appointment_declined']
            );
        }
        $this->notifyWebAdmin(
            $appointment,
            'appointment_doctor_decision_'.$action,
            'Doctor updated appointment',
            'Appointment #'.$appointment->id.' doctor action: '.$action.'.'
        );

        return response()->json([
            'status' => true,
            'message' => 'Appointment updated successfully.',
            'data' => $this->appointmentPayload($appointment, false),
        ]);
    }

    public function farmerApproval(Request $request, DoctorAppointment $appointment)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected', 'cancelled'])],
        ]);

        $status = $data['status'] === 'approved' ? 'approved' : 'cancelled';
        $otpCode = $status === 'approved' ? (string) random_int(100000, 999999) : null;

        $appointment->update([
            'status' => $status,
            'farmer_approved_at' => $status === 'approved' ? now() : null,
            'otp_code' => $otpCode,
            'otp_verified_at' => null,
            'treatment_started_at' => null,
            'doctor_live_latitude' => null,
            'doctor_live_longitude' => null,
            'doctor_live_updated_at' => null,
        ]);

        $appointment->refresh()->loadMissing(['doctor', 'farmer']);
        if ($status === 'approved') {
            $this->notifyDoctor(
                $appointment,
                'Appointment Confirmed',
                'Farmer approved your appointment. Visit OTP will be provided at location.',
                ['event' => 'appointment_farmer_approved']
            );
            $this->notifyFarmer(
                $appointment,
                'Visit OTP Generated',
                'Share this OTP with doctor at visit time: '.$otpCode,
                ['event' => 'appointment_visit_otp']
            );
        } else {
            $this->notifyDoctor(
                $appointment,
                'Appointment Cancelled',
                'Farmer cancelled this appointment.',
                ['event' => 'appointment_farmer_cancelled']
            );
        }
        $this->notifyWebAdmin(
            $appointment,
            'appointment_farmer_'.$status,
            'Farmer updated appointment',
            'Appointment #'.$appointment->id.' farmer status: '.$status.'.'
        );

        return response()->json([
            'status' => true,
            'message' => $status === 'approved'
                ? 'Appointment approved by farmer.'
                : 'Appointment rejected by farmer.',
            'data' => $this->appointmentPayload($appointment, true),
        ]);
    }

    public function verifyOtp(Request $request, DoctorAppointment $appointment)
    {
        $data = $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        if (blank($appointment->otp_code)) {
            return response()->json([
                'status' => false,
                'message' => 'Visit OTP is not generated yet for this appointment.',
            ], 422);
        }

        if ((string) $appointment->otp_code !== (string) $data['otp']) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid OTP. Please enter the OTP shared by farmer.',
            ], 422);
        }

        $appointment->update([
            'otp_verified_at' => now(),
            'status' => in_array($appointment->status, ['approved', 'rescheduled'], true)
                ? 'approved'
                : $appointment->status,
        ]);

        $appointment->refresh()->loadMissing(['doctor', 'farmer']);
        $this->notifyFarmer(
            $appointment,
            'OTP Verified',
            'Doctor verified your appointment OTP successfully.',
            ['event' => 'appointment_otp_verified']
        );
        $this->notifyWebAdmin(
            $appointment,
            'appointment_otp_verified',
            'Appointment OTP verified',
            'OTP verified for appointment #'.$appointment->id.'.'
        );

        return response()->json([
            'status' => true,
            'message' => 'OTP verified successfully.',
            'data' => $this->appointmentPayload($appointment, false),
        ]);
    }

    public function startTreatment(Request $request, DoctorAppointment $appointment)
    {
        $data = $request->validate([
            'notes' => ['nullable', 'string'],
        ]);

        if ($appointment->otp_verified_at === null) {
            return response()->json([
                'status' => false,
                'message' => 'Please verify appointment OTP before starting treatment.',
            ], 422);
        }

        $appointment->update([
            'status' => 'in_progress',
            'treatment_started_at' => now(),
            'notes' => $data['notes'] ?? $appointment->notes,
        ]);

        $appointment->refresh()->loadMissing(['doctor', 'farmer']);
        $this->notifyFarmer(
            $appointment,
            'Treatment Started',
            'Doctor started treatment for this appointment.',
            ['event' => 'treatment_started']
        );
        $this->notifyWebAdmin(
            $appointment,
            'treatment_started',
            'Treatment started',
            'Doctor started treatment for appointment #'.$appointment->id.'.'
        );

        return response()->json([
            'status' => true,
            'message' => 'Treatment started successfully.',
            'data' => $this->appointmentPayload($appointment, true),
        ]);
    }

    public function updateTreatment(Request $request, DoctorAppointment $appointment)
    {
        $data = $request->validate([
            'treatment_details' => ['required', 'string'],
            'onsite_treatment' => ['nullable', 'string'],
            'followup_required' => ['nullable', 'boolean'],
            'next_followup_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        if (! empty($data['followup_required']) && empty($data['next_followup_date'])) {
            return response()->json([
                'status' => false,
                'message' => 'next_followup_date is required when follow-up is selected.',
            ], 422);
        }

        $appointment->update([
            'treatment_details' => $data['treatment_details'],
            'onsite_treatment' => $data['onsite_treatment']
                ?? $this->extractOnsiteTreatment($data['treatment_details'])
                ?? $appointment->onsite_treatment,
            'followup_required' => $data['followup_required'] ?? $appointment->followup_required,
            'next_followup_date' => $data['next_followup_date'] ?? $appointment->next_followup_date,
            'notes' => $data['notes'] ?? $appointment->notes,
        ]);

        $appointment->refresh()->loadMissing(['doctor', 'farmer']);
        $summary = Str::limit((string) $data['treatment_details'], 120);
        $this->notifyFarmer(
            $appointment,
            'Treatment details updated',
            $summary !== '' ? $summary : 'Doctor saved treatment notes for your animal.',
            ['event' => 'appointment_treatment_updated']
        );

        if (! empty($data['followup_required'])) {
            $this->notifyFarmer(
                $appointment,
                'Follow-up visit suggested',
                'Your doctor indicated a follow-up appointment may be needed.',
                ['event' => 'appointment_followup_suggested']
            );
        }
        $this->notifyWebAdmin(
            $appointment,
            'appointment_treatment_updated',
            'Treatment details updated',
            'Treatment details updated for appointment #'.$appointment->id.'.'
        );

        return response()->json([
            'status' => true,
            'message' => 'Treatment details saved.',
            'data' => $this->appointmentPayload($appointment->fresh()->loadMissing(['doctor', 'farmer']), true),
        ]);
    }

    public function updateLiveLocation(Request $request, DoctorAppointment $appointment)
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $appointment->update([
            'doctor_live_latitude' => $data['latitude'],
            'doctor_live_longitude' => $data['longitude'],
            'doctor_live_updated_at' => now(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Doctor live location updated successfully.',
            'data' => $this->appointmentPayload($appointment->fresh()->loadMissing(['doctor', 'farmer']), true),
        ]);
    }

    protected function appointmentPayload(DoctorAppointment $appointment, bool $includeOtp = false): array
    {
        $followupDueToday = $this->isFollowupDueToday($appointment);
        $effectiveStatus = $followupDueToday ? 'followup' : ($appointment->status ?? 'pending');

        $diseaseIds = collect($appointment->disease_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->values();

        $diseases = [];
        if (! $diseaseIds->isEmpty()) {
            try {
                $diseases = DoctorDisease::query()
                    ->whereIn('id', $diseaseIds->all())
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get()
                    ->map(fn (DoctorDisease $disease) => [
                        'id' => $disease->id,
                        'name' => $disease->name,
                    ])
                    ->values()
                    ->all();
            } catch (\Throwable $exception) {
                $diseases = [];
            }
        }

        return [
            'id' => $appointment->id,
            'appointment_group_id' => $appointment->appointment_group_id,
            'doctor_id' => $appointment->doctor_id,
            'doctor_name' => optional($appointment->doctor)->full_name ?? '',
            'farmer_id' => $appointment->farmer_id,
            'animal_id' => $appointment->animal_id,
            'farmer_name' => $appointment->farmer_name ?? '',
            'farmer_phone' => $appointment->farmer_phone ?? '',
            'animal_name' => $appointment->animal_name ?? '',
            'animal_photo_url' => $appointment->animal_photo_url,
            'concern' => $appointment->concern ?? '',
            'disease_ids' => $diseaseIds->all(),
            'disease_details' => $appointment->disease_details ?? '',
            'diseases' => $diseases,
            'status' => $appointment->status ?? 'pending',
            'effective_status' => $effectiveStatus,
            'followup_due_today' => $followupDueToday,
            'requested_at' => optional($appointment->requested_at)->toIso8601String(),
            'scheduled_at' => optional($appointment->scheduled_at)->toIso8601String(),
            'completed_at' => optional($appointment->completed_at)->toIso8601String(),
            'otp_verified_at' => optional($appointment->otp_verified_at)->toIso8601String(),
            'treatment_started_at' => optional($appointment->treatment_started_at)->toIso8601String(),
            'treatment_details' => $appointment->treatment_details ?? '',
            'onsite_treatment' => $appointment->onsite_treatment ?? '',
            'followup_required' => (bool) ($appointment->followup_required ?? false),
            'next_followup_date' => optional($appointment->next_followup_date)->toDateString(),
            'charges' => $appointment->charges !== null ? (float) $appointment->charges : null,
            'latitude' => $appointment->latitude !== null ? (float) $appointment->latitude : null,
            'longitude' => $appointment->longitude !== null ? (float) $appointment->longitude : null,
            'doctor_live_latitude' => $appointment->doctor_live_latitude !== null ? (float) $appointment->doctor_live_latitude : null,
            'doctor_live_longitude' => $appointment->doctor_live_longitude !== null ? (float) $appointment->doctor_live_longitude : null,
            'doctor_live_updated_at' => optional($appointment->doctor_live_updated_at)->toIso8601String(),
            'visit_otp' => $includeOtp ? ($appointment->otp_code ?? null) : null,
            'address' => $appointment->address ?? '',
            'notes' => $appointment->notes ?? '',
            'created_at' => optional($appointment->created_at)->toIso8601String(),
        ];
    }

    protected function farmerStatusRank(DoctorAppointment $appointment): int
    {
        $status = strtolower((string) ($appointment->status ?? ''));

        if ($this->isFollowupDueToday($appointment)) {
            return 8;
        }

        return match ($status) {
            'in_progress' => 7,
            'approved', 'farmer_approved', 'scheduled', 'rescheduled' => 6,
            'proposed', 'awaiting_farmer_approval', 'awaiting_approval' => 5,
            'pending', 'new', 'requested' => 4,
            'completed' => 3,
            'declined', 'cancelled', 'rejected' => 2,
            default => 1,
        };
    }

    protected function notifyDoctor(DoctorAppointment $appointment, string $title, string $body, array $extraData = []): void
    {
        $appointment->loadMissing(['doctor', 'farmer']);
        $token = optional($appointment->doctor)->fcm_token;
        if (blank($token) && ! empty($appointment->doctor_id)) {
            $fallbackDoctor = Doctor::find((int) $appointment->doctor_id);
            $token = optional($fallbackDoctor)->fcm_token;
        }

        $this->firebaseService->sendToDevice(
            $token,
            $title,
            $body,
            $this->notificationData($appointment, $extraData)
        );
    }

    protected function notifyFarmer(DoctorAppointment $appointment, string $title, string $body, array $extraData = []): void
    {
        $appointment->loadMissing(['doctor', 'farmer']);
        $token = optional($appointment->farmer)->fcm_token;
        if (blank($token)) {
            $fallbackFarmer = null;
            if (! empty($appointment->farmer_id)) {
                $fallbackFarmer = Farmer::find((int) $appointment->farmer_id);
            }
            if (! $fallbackFarmer && ! empty($appointment->farmer_phone)) {
                $fallbackFarmer = Farmer::query()
                    ->where('mobile', (string) $appointment->farmer_phone)
                    ->first();
            }
            $token = optional($fallbackFarmer)->fcm_token;
        }

        $this->firebaseService->sendToDevice(
            $token,
            $title,
            $body,
            $this->notificationData($appointment, $extraData)
        );
    }

    protected function notificationData(DoctorAppointment $appointment, array $extraData = []): array
    {
        $effectiveStatus = $this->isFollowupDueToday($appointment)
            ? 'followup'
            : (string) ($appointment->status ?? '');

        $base = [
            'type' => 'doctor_appointment',
            'appointment_id' => (string) $appointment->id,
            'doctor_id' => (string) ($appointment->doctor_id ?? ''),
            'farmer_id' => (string) ($appointment->farmer_id ?? ''),
            'status' => (string) ($appointment->status ?? ''),
            'effective_status' => $effectiveStatus,
        ];

        foreach ($extraData as $key => $value) {
            $base[$key] = is_scalar($value) ? (string) $value : json_encode($value);
        }

        return $base;
    }

    protected function notifyWebAdmin(DoctorAppointment $appointment, string $event, string $title, string $message): void
    {
        DoctorAdminNotification::create([
            'doctor_appointment_id' => $appointment->id,
            'event' => $event,
            'title' => $title,
            'message' => $message,
            'is_read' => false,
        ]);

        $this->firebaseService->sendToWebAdmins(
            $title,
            $message,
            [
                'type' => 'web_admin',
                'event' => $event,
                'appointment_id' => (string) $appointment->id,
                'doctor_id' => (string) ($appointment->doctor_id ?? ''),
                'farmer_id' => (string) ($appointment->farmer_id ?? ''),
                'status' => (string) ($appointment->status ?? ''),
            ]
        );
    }

    protected function extractOnsiteTreatment(?string $treatmentDetails): ?string
    {
        $treatmentDetails = trim((string) $treatmentDetails);

        if ($treatmentDetails === '') {
            return null;
        }

        if (preg_match('/On-Site-Treatment:\s*(.+)/i', $treatmentDetails, $matches) === 1) {
            return trim((string) ($matches[1] ?? '')) ?: null;
        }

        return null;
    }

    protected function storeAnimalPhoto($file): string
    {
        $directory = public_path('assets/doctor_appointment_images');
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $filename = 'appointment_'.time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
        $file->move($directory, $filename);

        return 'assets/doctor_appointment_images/'.$filename;
    }

    protected function isFollowupDueToday(DoctorAppointment $appointment): bool
    {
        return (bool) ($appointment->followup_required ?? false)
            && ($appointment->status ?? '') === 'completed'
            && ! empty($appointment->next_followup_date)
            && optional($appointment->next_followup_date)->toDateString() === now()->toDateString();
    }
}
