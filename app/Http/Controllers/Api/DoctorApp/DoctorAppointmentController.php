<?php

namespace App\Http\Controllers\Api\DoctorApp;

use App\Http\Controllers\Controller;
use App\Models\Doctor\Doctor;
use App\Models\Doctor\DoctorAppointment;
use App\Models\Farmer\Animal;
use App\Models\Farmer\Farmer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DoctorAppointmentController extends Controller
{
    public function indexByDoctor(Doctor $doctor)
    {
        $appointments = DoctorAppointment::query()
            ->where('doctor_id', $doctor->id)
            ->latest('requested_at')
            ->latest()
            ->get()
            ->map(fn (DoctorAppointment $appointment) => $this->appointmentPayload($appointment));

        return response()->json([
            'status' => true,
            'message' => 'Doctor appointments fetched successfully.',
            'data' => $appointments,
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
            'farmer_id' => $data['farmer_id'] ?? null,
            'animal_id' => $data['animal_id'] ?? null,
            'farmer_name' => $data['farmer_name']
                ?? ($farmer ? trim(($farmer->first_name ?? '').' '.($farmer->last_name ?? '')) : null),
            'farmer_phone' => $data['farmer_phone'] ?? ($farmer->mobile ?? null),
            'animal_name' => $data['animal_name'] ?? ($animal->animal_name ?? null),
            'animal_photo' => $animalPhoto,
            'concern' => $data['concern'],
            'status' => 'pending',
            'requested_at' => $data['requested_at'] ?? now(),
            'address' => $data['address'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Appointment request created successfully.',
            'data' => $this->appointmentPayload($appointment),
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

        return response()->json([
            'status' => true,
            'message' => 'Appointment slot and charges shared with farmer.',
            'data' => $this->appointmentPayload($appointment->fresh()),
        ]);
    }

    public function complete(Request $request, DoctorAppointment $appointment)
    {
        $data = $request->validate([
            'notes' => ['nullable', 'string'],
        ]);

        $appointment->update([
            'status' => 'completed',
            'completed_at' => now(),
            'notes' => $data['notes'] ?? $appointment->notes,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Appointment marked as completed.',
            'data' => $this->appointmentPayload($appointment->fresh()),
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
            $appointment->update([
                'status' => 'approved',
                'farmer_approved_at' => now(),
            ]);
        } else {
            $appointment->update([
                'status' => 'declined',
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Appointment updated successfully.',
            'data' => $this->appointmentPayload($appointment->fresh()),
        ]);
    }

    public function farmerApproval(Request $request, DoctorAppointment $appointment)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected', 'cancelled'])],
        ]);

        $status = $data['status'] === 'approved' ? 'approved' : 'cancelled';

        $appointment->update([
            'status' => $status,
            'farmer_approved_at' => $status === 'approved' ? now() : null,
        ]);

        return response()->json([
            'status' => true,
            'message' => $status === 'approved'
                ? 'Appointment approved by farmer.'
                : 'Appointment rejected by farmer.',
            'data' => $this->appointmentPayload($appointment->fresh()),
        ]);
    }

    protected function appointmentPayload(DoctorAppointment $appointment): array
    {
        return [
            'id' => $appointment->id,
            'doctor_id' => $appointment->doctor_id,
            'farmer_id' => $appointment->farmer_id,
            'animal_id' => $appointment->animal_id,
            'farmer_name' => $appointment->farmer_name ?? '',
            'farmer_phone' => $appointment->farmer_phone ?? '',
            'animal_name' => $appointment->animal_name ?? '',
            'animal_photo_url' => $appointment->animal_photo_url,
            'concern' => $appointment->concern ?? '',
            'status' => $appointment->status ?? 'pending',
            'requested_at' => optional($appointment->requested_at)->toIso8601String(),
            'scheduled_at' => optional($appointment->scheduled_at)->toIso8601String(),
            'completed_at' => optional($appointment->completed_at)->toIso8601String(),
            'charges' => $appointment->charges !== null ? (float) $appointment->charges : null,
            'latitude' => $appointment->latitude !== null ? (float) $appointment->latitude : null,
            'longitude' => $appointment->longitude !== null ? (float) $appointment->longitude : null,
            'address' => $appointment->address ?? '',
            'notes' => $appointment->notes ?? '',
            'created_at' => optional($appointment->created_at)->toIso8601String(),
        ];
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
}
