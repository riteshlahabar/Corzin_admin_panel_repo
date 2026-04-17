<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Models\Doctor\Doctor;
use App\Models\Doctor\DoctorAppointment;
use App\Models\Farmer\Animal;
use App\Models\Farmer\Farmer;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DoctorAppointmentController extends \App\Http\Controllers\Api\DoctorApp\DoctorAppointmentController
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'doctor_id' => ['nullable', 'exists:doctors,id'],
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
        if (!empty($data['farmer_id'])) {
            $farmer = Farmer::find($data['farmer_id']);
        } elseif (! empty($data['farmer_phone'])) {
            $farmer = Farmer::query()
                ->where('mobile', (string) $data['farmer_phone'])
                ->first();
        }

        $animal = null;
        if (!empty($data['animal_id'])) {
            $animal = Animal::find($data['animal_id']);
            if (! $animal) {
                return response()->json([
                    'status' => false,
                    'message' => 'Selected animal not found. Please refresh and try again.',
                ], 422);
            }

            $resolvedFarmerId = (int) ($data['farmer_id'] ?? ($farmer->id ?? 0));
            if ($resolvedFarmerId > 0 && (int) $animal->farmer_id !== $resolvedFarmerId) {
                return response()->json([
                    'status' => false,
                    'message' => 'Selected animal does not belong to this farmer.',
                ], 422);
            }
        }

        $animalPhoto = $data['animal_photo'] ?? null;
        if ($request->hasFile('animal_photo_file')) {
            $animalPhoto = $this->storeAnimalPhoto($request->file('animal_photo_file'));
        } elseif ($animal && !empty($animal->image)) {
            $animalPhoto = $animal->image;
        }

        $targetDoctors = $this->resolveTargetDoctors($data, $farmer);
        if ($targetDoctors->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No nearby approved doctor found for this appointment.',
            ], 422);
        }

        $groupId = (string) Str::uuid();
        $appointments = collect();
        foreach ($targetDoctors as $doctor) {
            $appointment = DoctorAppointment::create([
                'doctor_id' => $doctor->id,
                'appointment_group_id' => $groupId,
                'farmer_id' => $data['farmer_id'] ?? ($farmer->id ?? null),
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

            $appointments->push($appointment);
        }

        /** @var DoctorAppointment $primaryAppointment */
        $primaryAppointment = $appointments->first();
        $broadcastCount = $appointments->count();
        $this->notifyWebAdmin(
            $primaryAppointment,
            'appointment_created_broadcast',
            'New appointment request (broadcast)',
            trim(($primaryAppointment->farmer_name ?? 'Farmer').' appointment broadcast to '.$broadcastCount.' doctor(s).')
        );

        return response()->json([
            'status' => true,
            'message' => $broadcastCount > 1
                ? "Appointment broadcast to {$broadcastCount} nearby doctors."
                : 'Appointment request created successfully.',
            'data' => $this->appointmentPayload($primaryAppointment, true),
            'broadcast_count' => $broadcastCount,
            'appointment_ids' => $appointments->pluck('id')->values(),
        ], 201);
    }

    protected function resolveTargetDoctors(array $data, ?Farmer $farmer): Collection
    {
        if (!empty($data['doctor_id'])) {
            return Doctor::query()
                ->where('status', 'approved')
                ->whereKey((int) $data['doctor_id'])
                ->get();
        }

        $approvedDoctors = Doctor::query()->where('status', 'approved');
        $ranked = collect();

        $pincode = trim((string) ($farmer->pincode ?? ''));
        $city = trim((string) ($farmer->city ?? ''));
        $district = trim((string) ($farmer->district ?? ''));
        $state = trim((string) ($farmer->state ?? ''));

        if ($pincode !== '') {
            $byPincode = (clone $approvedDoctors)
                ->where('pincode', $pincode)
                ->orderByDesc('approved_at')
                ->limit(10)
                ->get();
            if ($byPincode->isNotEmpty()) {
                $ranked = $ranked->concat($byPincode);
            }
        }

        if ($city !== '' || $district !== '') {
            $byCity = (clone $approvedDoctors);
            if ($city !== '') {
                $byCity->where('city', $city);
            }
            if ($district !== '') {
                $byCity->where('district', $district);
            }

            $cityDoctors = $byCity->orderByDesc('approved_at')->limit(10)->get();
            if ($cityDoctors->isNotEmpty()) {
                $ranked = $ranked->concat($cityDoctors);
            }
        }

        if ($district !== '') {
            $byDistrict = (clone $approvedDoctors)->where('district', $district);
            if ($state !== '') {
                $byDistrict->where('state', $state);
            }

            $districtDoctors = $byDistrict->orderByDesc('approved_at')->limit(10)->get();
            if ($districtDoctors->isNotEmpty()) {
                $ranked = $ranked->concat($districtDoctors);
            }
        }

        // Always include all approved doctors as fallback broadcast pool,
        // while keeping nearby doctors first in order.
        $allApproved = (clone $approvedDoctors)
            ->orderByDesc('approved_at')
            ->limit(50)
            ->get();

        return $ranked
            ->concat($allApproved)
            ->unique('id')
            ->values()
            ->take(50);
    }
}
