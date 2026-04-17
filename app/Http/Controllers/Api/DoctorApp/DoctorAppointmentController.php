<?php

namespace App\Http\Controllers\Api\DoctorApp;

use App\Http\Controllers\Controller;
use App\Models\Doctor\DoctorAdminNotification;
use App\Models\Doctor\Doctor;
use App\Models\Doctor\DoctorAppointment;
use App\Models\Doctor\DoctorDisease;
use App\Models\NotificationTemplate;
use App\Models\Farmer\Animal;
use App\Models\Farmer\Farmer;
use App\Models\Farmer\FeedingRecord;
use App\Models\Farmer\MilkProduction;
use App\Models\Reproductive\ReproductiveRecord;
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
            ->whereNotIn('status', ['cancelled', 'declined', 'rejected'])
            ->where(function ($query) {
                $query->whereNotNull('notified_at')
                    ->orWhereNotIn('status', ['pending']);
            })
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
                // Safeguard for broadcast race conditions:
                // if there is at least one active row in this group,
                // ignore cancelled/declined/rejected rows.
                $active = $items->filter(function (DoctorAppointment $row) {
                    return ! in_array(
                        strtolower((string) ($row->status ?? '')),
                        ['cancelled', 'declined', 'rejected'],
                        true
                    );
                });
                if ($active->isNotEmpty()) {
                    $items = $active;
                }

                $items = $items->sort(function (DoctorAppointment $a, DoctorAppointment $b) {
                    $rankDiff = $this->farmerStatusRank($b) <=> $this->farmerStatusRank($a);
                    if ($rankDiff !== 0) {
                        return $rankDiff;
                    }

                    // Prefer most recently updated row when ranks are same
                    // (important when one doctor just accepted and others got cancelled).
                    $aTime = optional($a->updated_at ?: $a->requested_at ?: $a->created_at)->timestamp ?? 0;
                    $bTime = optional($b->updated_at ?: $b->requested_at ?: $b->created_at)->timestamp ?? 0;
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
            'send_otp' => ['nullable'],
        ]);

        $action = $data['action'];
        $sendOtpRaw = $request->input('send_otp', $data['send_otp'] ?? '0');
        $sendOtp = in_array(
            strtolower(trim((string) $sendOtpRaw)),
            ['1', 'true', 'yes', 'on'],
            true
        );

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
            $groupId = $appointment->appointment_group_id;
            if (! blank($groupId)) {
                $alreadyAccepted = DoctorAppointment::query()
                    ->where('appointment_group_id', $groupId)
                    ->where('id', '!=', $appointment->id)
                    ->whereIn('status', ['approved', 'in_progress', 'completed'])
                    ->exists();

                if ($alreadyAccepted) {
                    return response()->json([
                        'status' => false,
                        'message' => 'This appointment is already accepted by another doctor.',
                    ], 409);
                }
            }

            $updateData = [
                'status' => 'approved',
                'farmer_approved_at' => now(),
            ];
            if ($sendOtp) {
                $updateData['otp_code'] = (string) random_int(100000, 999999);
                $updateData['otp_verified_at'] = null;
            }
            $appointment->update($updateData);

            // If one doctor accepts from a broadcast group, close the same request
            // for all other doctors so it disappears from their appointment screen.
            if (! blank($groupId)) {
                $otherRows = DoctorAppointment::query()
                    ->where('appointment_group_id', $groupId)
                    ->where('id', '!=', $appointment->id)
                    ->whereNotIn('status', ['cancelled', 'declined', 'rejected', 'completed'])
                    ->get();

                if ($otherRows->isNotEmpty()) {
                    DoctorAppointment::query()
                        ->whereIn('id', $otherRows->pluck('id'))
                        ->update(['status' => 'cancelled']);

                    foreach ($otherRows as $other) {
                        $other->refresh()->loadMissing(['doctor', 'farmer']);
                        $this->notifyDoctor(
                            $other,
                            'Appointment Closed',
                            'This appointment was accepted by another nearby doctor.',
                            ['event' => 'appointment_taken_by_other_doctor']
                        );
                    }
                }
            }
        } else {
            $appointment->update([
                'status' => 'declined',
            ]);
        }

        $appointment->refresh()->loadMissing(['doctor', 'farmer']);
        if ($action === 'approved') {
            if ($sendOtp) {
                $this->notifyFarmer(
                    $appointment,
                    'Send OTP For Doctor Visit',
                    'Doctor sent visit OTP. Share this OTP at visit time: '.($appointment->otp_code ?? ''),
                    ['event' => 'appointment_visit_otp_sent']
                );
            } else {
                $this->notifyFarmer(
                    $appointment,
                    'Appointment Accepted',
                    'Doctor accepted your appointment request.',
                    ['event' => 'appointment_accepted']
                );
            }
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
            'message' => $action === 'approved'
                ? ($sendOtp ? 'OTP sent to farmer for doctor visit.' : 'Appointment accepted successfully.')
                : 'Appointment updated successfully.',
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
        $previousSelfHistories = $this->previousSelfTreatmentHistories($appointment);
        $recentMilkHistory = $this->recentMilkHistory($appointment);
        $recentFeedingHistory = $this->recentFeedingHistory($appointment);
        $recentPregnancyHistory = $this->recentPregnancyHistory($appointment);

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

        $farmer = $appointment->farmer;
        if (! $farmer && ! empty($appointment->farmer_phone)) {
            $farmer = Farmer::query()
                ->where('mobile', (string) $appointment->farmer_phone)
                ->first();
        }

        $farmerFullName = trim(implode(' ', array_filter([
            $farmer->first_name ?? null,
            $farmer->middle_name ?? null,
            $farmer->last_name ?? null,
        ])));
        if ($farmerFullName === '') {
            $farmerFullName = (string) ($appointment->farmer_name ?? '');
        }

        return [
            'id' => $appointment->id,
            'appointment_code' => $appointment->appointment_code,
            'appointment_group_id' => $appointment->appointment_group_id,
            'doctor_id' => $appointment->doctor_id,
            'doctor_name' => optional($appointment->doctor)->full_name ?? '',
            'farmer_id' => $appointment->farmer_id,
            'animal_id' => $appointment->animal_id,
            'farmer_name' => $farmerFullName,
            'farmer_first_name' => (string) ($farmer->first_name ?? ''),
            'farmer_middle_name' => (string) ($farmer->middle_name ?? ''),
            'farmer_last_name' => (string) ($farmer->last_name ?? ''),
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
            'previous_histories' => $previousSelfHistories,
            'recent_milk_history' => $recentMilkHistory,
            'recent_feeding_history' => $recentFeedingHistory,
            'recent_pregnancy_history' => $recentPregnancyHistory,
            'created_at' => optional($appointment->created_at)->toIso8601String(),
        ];
    }

    protected function previousSelfTreatmentHistories(DoctorAppointment $appointment): array
    {
        $query = DoctorAppointment::query()
            ->with('doctor')
            ->where('status', 'completed')
            ->where('id', '<>', $appointment->id);

        if (! empty($appointment->farmer_id)) {
            $query->where('farmer_id', $appointment->farmer_id);
        } elseif (! empty($appointment->farmer_phone)) {
            $query->where('farmer_phone', $appointment->farmer_phone);
        } else {
            return [];
        }

        if (! empty($appointment->animal_id)) {
            $query->where('animal_id', $appointment->animal_id);
        } elseif (! empty($appointment->animal_name)) {
            $query->where('animal_name', $appointment->animal_name);
        }

        return $query
            ->latest('completed_at')
            ->latest('requested_at')
            ->limit(10)
            ->get()
            ->map(function (DoctorAppointment $row) {
                return [
                    'id' => $row->id,
                    'status' => $row->status ?? '',
                    'requested_at' => optional($row->requested_at)->toIso8601String(),
                    'completed_at' => optional($row->completed_at)->toIso8601String(),
                    'concern' => $row->concern ?? '',
                    'treatment_details' => $row->treatment_details ?? '',
                    'onsite_treatment' => $row->onsite_treatment ?? '',
                    'notes' => $row->notes ?? '',
                    'doctor_id' => $row->doctor_id,
                    'doctor_name' => optional($row->doctor)->full_name ?? '',
                ];
            })
            ->values()
            ->all();
    }

    protected function recentMilkHistory(DoctorAppointment $appointment): array
    {
        if (empty($appointment->animal_id)) {
            return [];
        }

        return MilkProduction::query()
            ->where('animal_id', $appointment->animal_id)
            ->whereDate('date', '>=', now()->subDays(5)->toDateString())
            ->orderByDesc('date')
            ->limit(5)
            ->get()
            ->map(fn (MilkProduction $row) => [
                'date' => optional($row->date)->toDateString(),
                'morning_milk' => $row->morning_milk !== null ? (float) $row->morning_milk : null,
                'afternoon_milk' => $row->afternoon_milk !== null ? (float) $row->afternoon_milk : null,
                'evening_milk' => $row->evening_milk !== null ? (float) $row->evening_milk : null,
                'total_milk' => $row->total_milk !== null ? (float) $row->total_milk : null,
                'fat' => $row->fat !== null ? (float) $row->fat : null,
                'snf' => $row->snf !== null ? (float) $row->snf : null,
            ])
            ->values()
            ->all();
    }

    protected function recentFeedingHistory(DoctorAppointment $appointment): array
    {
        if (empty($appointment->animal_id)) {
            return [];
        }

        return FeedingRecord::query()
            ->with('feedType')
            ->where('animal_id', $appointment->animal_id)
            ->whereDate('date', '>=', now()->subDays(5)->toDateString())
            ->orderByDesc('date')
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (FeedingRecord $row) => [
                'date' => optional($row->date)->toDateString(),
                'feeding_time' => (string) ($row->feeding_time ?? ''),
                'feed_type' => optional($row->feedType)->name ?? '',
                'quantity' => $row->quantity !== null ? (float) $row->quantity : null,
                'unit' => (string) ($row->unit ?? ''),
                'notes' => (string) ($row->notes ?? ''),
            ])
            ->values()
            ->all();
    }

    protected function recentPregnancyHistory(DoctorAppointment $appointment): array
    {
        if (empty($appointment->animal_id)) {
            return [];
        }

        $since = now()->subMonths(6)->toDateString();

        return ReproductiveRecord::query()
            ->where('animal_id', $appointment->animal_id)
            ->where(function ($query) use ($since) {
                $query->whereDate('ai_date', '>=', $since)
                    ->orWhereDate('calving_date', '>=', $since)
                    ->orWhere('pregnancy_confirmation', true);
            })
            ->latest('ai_date')
            ->latest('calving_date')
            ->limit(10)
            ->get()
            ->map(fn (ReproductiveRecord $row) => [
                'ai_date' => optional($row->ai_date)->toDateString(),
                'calving_date' => optional($row->calving_date)->toDateString(),
                'breed_name' => (string) ($row->breed_name ?? ''),
                'lactation_number' => $row->lactation_number !== null ? (int) $row->lactation_number : null,
                'pregnancy_confirmation' => (bool) ($row->pregnancy_confirmation ?? false),
                'notes' => (string) ($row->notes ?? ''),
            ])
            ->values()
            ->all();
    }

    protected function farmerStatusRank(DoctorAppointment $appointment): int
    {
        $status = strtolower((string) ($appointment->status ?? ''));

        if ($this->isFollowupDueToday($appointment)) {
            return 10;
        }

        return match ($status) {
            'completed' => 9,
            'in_progress' => 8,
            'approved', 'farmer_approved', 'scheduled', 'rescheduled' => 7,
            'proposed', 'awaiting_farmer_approval', 'awaiting_approval' => 6,
            'pending', 'new', 'requested' => 5,
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

        [$finalTitle, $finalBody] = $this->resolveTemplateMessage(
            (string) ($extraData['event'] ?? ''),
            $title,
            $body,
            $appointment,
            $extraData
        );

        $this->firebaseService->sendToDevice(
            $token,
            $finalTitle,
            $finalBody,
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

        [$finalTitle, $finalBody] = $this->resolveTemplateMessage(
            (string) ($extraData['event'] ?? ''),
            $title,
            $body,
            $appointment,
            $extraData
        );

        $this->firebaseService->sendToDevice(
            $token,
            $finalTitle,
            $finalBody,
            $this->notificationData($appointment, $extraData)
        );
    }

    protected function resolveTemplateMessage(
        string $eventKey,
        string $fallbackTitle,
        string $fallbackBody,
        DoctorAppointment $appointment,
        array $extraData = []
    ): array {
        $eventKey = trim($eventKey);
        if ($eventKey === '') {
            return [$fallbackTitle, $fallbackBody];
        }

        $template = NotificationTemplate::query()
            ->where('template_key', $eventKey)
            ->where('is_active', true)
            ->first();

        if (! $template) {
            return [$fallbackTitle, $fallbackBody];
        }

        $vars = array_merge([
            'appointment_id' => (string) ($appointment->appointment_code ?? $appointment->id),
            'doctor_id' => (string) ($appointment->doctor_id ?? ''),
            'doctor_name' => (string) (optional($appointment->doctor)->full_name ?? ''),
            'farmer_id' => (string) ($appointment->farmer_id ?? ''),
            'farmer_name' => (string) ($appointment->farmer_name ?? ''),
            'animal_name' => (string) ($appointment->animal_name ?? ''),
            'status' => (string) ($appointment->status ?? ''),
            'otp' => (string) ($appointment->otp_code ?? ''),
        ], $extraData);

        $title = $this->replaceTemplateVars((string) $template->title_template, $vars);
        $body = $this->replaceTemplateVars((string) $template->body_template, $vars);

        return [
            trim($title) !== '' ? $title : $fallbackTitle,
            trim($body) !== '' ? $body : $fallbackBody,
        ];
    }

    protected function replaceTemplateVars(string $text, array $vars): string
    {
        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', function ($matches) use ($vars) {
            $key = (string) ($matches[1] ?? '');
            if ($key === '') {
                return '';
            }

            $value = $vars[$key] ?? '';
            if (is_scalar($value) || $value === null) {
                return (string) ($value ?? '');
            }

            return '';
        }, $text) ?? $text;
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
        if (! blank($appointment->otp_code)) {
            $base['otp'] = (string) $appointment->otp_code;
            $base['visit_otp'] = (string) $appointment->otp_code;
        }

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
