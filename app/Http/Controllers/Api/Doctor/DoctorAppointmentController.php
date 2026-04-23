<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Models\Doctor\Doctor;
use App\Models\Doctor\DoctorAppointment;
use App\Models\Farmer\Animal;
use App\Models\Farmer\Farmer;
use Illuminate\Support\Carbon;
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
        $requestTime = isset($data['requested_at']) ? Carbon::parse($data['requested_at']) : now();

        foreach ($targetDoctors as $doctor) {
            $distance = (float) ($doctor->distance_km ?? 0);
            $radiusFrom = $this->radiusBandFromDistance($distance);
            $radiusTo = $radiusFrom + 5;
            $sendNow = $radiusTo <= 5;

            $appointment = DoctorAppointment::create([
                'doctor_id' => $doctor->id,
                'appointment_group_id' => $groupId,
                'notify_radius_from_km' => $radiusFrom,
                'notify_radius_to_km' => $radiusTo,
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
                'requested_at' => $requestTime,
                'notified_at' => $sendNow ? now() : null,
                'address' => $data['address'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
            ]);

            if ($sendNow) {
                $appointment->loadMissing(['doctor', 'farmer']);
                $this->notifyDoctor(
                    $appointment,
                    'New Appointment Request',
                    trim(($appointment->farmer_name ?? 'Farmer').' requested a visit for '.($appointment->animal_name ?? 'animal')),
                    [
                        'event' => 'appointment_created',
                        'radius_from_km' => (string) $radiusFrom,
                        'radius_to_km' => (string) $radiusTo,
                    ]
                );
            }

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
                ? "Appointment created. First wave sent to 0-5 km doctors ({$broadcastCount} total queued up to 20 km)."
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
                ->where('is_active_for_appointments', true)
                ->whereKey((int) $data['doctor_id'])
                ->get();
        }
        $originLat = isset($data['latitude']) ? (float) $data['latitude'] : null;
        $originLng = isset($data['longitude']) ? (float) $data['longitude'] : null;

        if ($originLat !== null && $originLng !== null) {
            $distanceExpression = $this->distanceExpression($originLat, $originLng);

            return Doctor::query()
                ->where('status', 'approved')
                ->where('is_active_for_appointments', true)
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->select('doctors.*')
                ->selectRaw("{$distanceExpression} as distance_km")
                ->having('distance_km', '<=', 20)
                ->orderBy('distance_km')
                ->limit(80)
                ->get();
        }

        // Fallback when farmer coordinates are unavailable:
        // keep previous location ranking and treat all as first wave.
        $approvedDoctors = Doctor::query()
            ->where('status', 'approved')
            ->where('is_active_for_appointments', true);
        $ranked = collect();

        $pincode = trim((string) ($farmer->pincode ?? ''));
        $city = trim((string) ($farmer->city ?? ''));
        $district = trim((string) ($farmer->district ?? ''));
        $state = trim((string) ($farmer->state ?? ''));

        if ($pincode !== '') {
            $ranked = $ranked->concat(
                (clone $approvedDoctors)
                    ->where('pincode', $pincode)
                    ->orderByDesc('approved_at')
                    ->limit(20)
                    ->get()
            );
        }

        if ($city !== '' || $district !== '') {
            $byCity = (clone $approvedDoctors);
            if ($city !== '') $byCity->where('city', $city);
            if ($district !== '') $byCity->where('district', $district);
            $ranked = $ranked->concat($byCity->orderByDesc('approved_at')->limit(20)->get());
        }

        if ($district !== '') {
            $byDistrict = (clone $approvedDoctors)->where('district', $district);
            if ($state !== '') $byDistrict->where('state', $state);
            $ranked = $ranked->concat($byDistrict->orderByDesc('approved_at')->limit(20)->get());
        }

        $allApproved = (clone $approvedDoctors)->orderByDesc('approved_at')->limit(80)->get();

        return $ranked
            ->concat($allApproved)
            ->unique('id')
            ->values()
            ->map(function (Doctor $doctor) {
                $doctor->distance_km = 0;
                return $doctor;
            })
            ->take(80);
    }

    protected function radiusBandFromDistance(float $distanceKm): int
    {
        if ($distanceKm <= 5) return 0;
        if ($distanceKm <= 10) return 5;
        if ($distanceKm <= 15) return 10;
        return 15;
    }

    protected function distanceExpression(float $originLat, float $originLng): string
    {
        return "(6371 * acos(cos(radians({$originLat})) * cos(radians(doctors.latitude)) * cos(radians(doctors.longitude) - radians({$originLng})) + sin(radians({$originLat})) * sin(radians(doctors.latitude))))";
    }
}
