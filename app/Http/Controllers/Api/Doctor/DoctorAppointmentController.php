<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Models\Doctor\Doctor;
use App\Models\Doctor\DoctorAppointment;
use App\Models\Farmer\Animal;
use App\Models\Farmer\Farmer;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
        $debugTrace = (string) Str::uuid();

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

        Log::info('Appointment routing started', [
            'trace' => $debugTrace,
            'farmer_id' => $data['farmer_id'] ?? ($farmer->id ?? null),
            'farmer_phone' => $data['farmer_phone'] ?? ($farmer->mobile ?? null),
            'animal_id' => $data['animal_id'] ?? null,
            'doctor_id' => $data['doctor_id'] ?? null,
            'manual_address' => $data['address'] ?? null,
            'requested_at' => $data['requested_at'] ?? null,
        ]);

        $targetDoctors = $this->resolveTargetDoctors($data, $farmer, $debugTrace);
        if ($targetDoctors->isEmpty()) {
            Log::warning('Appointment routing found no doctors', [
                'trace' => $debugTrace,
                'farmer_id' => $data['farmer_id'] ?? ($farmer->id ?? null),
                'manual_address' => $data['address'] ?? null,
            ]);
            return response()->json([
                'status' => false,
                'message' => 'No nearby active doctor found from the farmer address for this appointment.',
            ], 422);
        }

        $groupId = (string) Str::uuid();
        $appointments = collect();
        $requestTime = isset($data['requested_at']) ? Carbon::parse($data['requested_at']) : now();

        foreach ($targetDoctors as $doctor) {
            $distance = (float) ($doctor->distance_km ?? 0);
            $radiusFrom = $this->radiusBandFromDistance($distance);
            $radiusTo = $radiusFrom + 5;
            $sendNow = $distance <= 5.0 || $radiusFrom === 0;

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
                'notified_at' => null,
                'address' => $data['address'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
            ]);

            Log::info('Appointment routing doctor candidate', [
                'trace' => $debugTrace,
                'appointment_id' => $appointment->id,
                'doctor_id' => $doctor->id,
                'doctor_name' => trim((string) (($doctor->full_name ?? '') ?: (($doctor->first_name ?? '').' '.($doctor->last_name ?? '')))),
                'doctor_latitude' => $doctor->latitude,
                'doctor_longitude' => $doctor->longitude,
                'doctor_last_live_location_at' => $doctor->last_live_location_at,
                'distance_km' => round($distance, 4),
                'radius_from_km' => $radiusFrom,
                'radius_to_km' => $radiusTo,
                'send_now' => $sendNow,
            ]);

            if ($sendNow) {
                $appointment->loadMissing(['doctor', 'farmer']);
                $sent = $this->notifyDoctor(
                    $appointment,
                    'New Appointment Request',
                    trim(($appointment->farmer_name ?? 'Farmer').' requested a visit for '.($appointment->animal_name ?? 'animal')),
                    [
                        'event' => 'appointment_created',
                        'radius_from_km' => (string) $radiusFrom,
                        'radius_to_km' => (string) $radiusTo,
                    ]
                );
                Log::info('Appointment routing first-wave notification result', [
                    'trace' => $debugTrace,
                    'appointment_id' => $appointment->id,
                    'doctor_id' => $doctor->id,
                    'sent' => $sent,
                ]);
                if ($sent) {
                    $appointment->notified_at = now();
                    $appointment->save();
                }
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

    protected function resolveTargetDoctors(array $data, ?Farmer $farmer, ?string $debugTrace = null): Collection
    {
        $origin = $this->resolveFarmerCoordinatesFromAddress($data, $farmer, $debugTrace);
        Log::info('Appointment routing farmer origin resolved', [
            'trace' => $debugTrace,
            'origin_latitude' => $origin['latitude'] ?? null,
            'origin_longitude' => $origin['longitude'] ?? null,
        ]);

        if (!empty($data['doctor_id'])) {
            $doctors = Doctor::query()
                ->where('status', 'approved')
                ->where('is_active_for_appointments', true)
                ->whereNotNull('last_live_location_at')
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->whereKey((int) $data['doctor_id'])
                ->get();

            Log::info('Appointment routing direct doctor match', [
                'trace' => $debugTrace,
                'requested_doctor_id' => (int) $data['doctor_id'],
                'matched_count' => $doctors->count(),
            ]);

            return $doctors;
        }

        if ($origin !== null) {
            $distanceExpression = $this->distanceExpression($origin['latitude'], $origin['longitude']);

            $doctors = Doctor::query()
                ->where('status', 'approved')
                ->where('is_active_for_appointments', true)
                ->whereNotNull('last_live_location_at')
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->select('doctors.*')
                ->selectRaw("{$distanceExpression} as distance_km")
                ->having('distance_km', '<=', 20)
                ->orderBy('distance_km')
                ->limit(80)
                ->get();

            Log::info('Appointment routing nearby doctor results', [
                'trace' => $debugTrace,
                'matched_count' => $doctors->count(),
                'doctors' => $doctors->map(fn (Doctor $doctor) => [
                    'doctor_id' => $doctor->id,
                    'doctor_name' => trim((string) (($doctor->full_name ?? '') ?: (($doctor->first_name ?? '').' '.($doctor->last_name ?? '')))),
                    'latitude' => $doctor->latitude,
                    'longitude' => $doctor->longitude,
                    'distance_km' => isset($doctor->distance_km) ? round((float) $doctor->distance_km, 4) : null,
                    'last_live_location_at' => $doctor->last_live_location_at,
                ])->values()->all(),
            ]);

            return $doctors;
        }

        Log::warning('Appointment routing missing farmer origin', [
            'trace' => $debugTrace,
            'farmer_id' => $data['farmer_id'] ?? ($farmer->id ?? null),
        ]);

        return collect();
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

    protected function resolveFarmerCoordinatesFromAddress(array $data, ?Farmer $farmer, ?string $debugTrace = null): ?array
    {
        $address = trim((string) ($data['address'] ?? ''));

        if ($address === '' && $farmer) {
            $address = collect([
                $farmer->village ?? '',
                $farmer->city ?? '',
                $farmer->district ?? '',
                $farmer->state ?? '',
                $farmer->pincode ?? '',
            ])->filter(fn ($value) => trim((string) $value) !== '')
                ->implode(', ');
        }

        if ($address === '') {
            Log::warning('Appointment routing address empty', [
                'trace' => $debugTrace,
                'farmer_id' => $data['farmer_id'] ?? ($farmer->id ?? null),
            ]);
            return null;
        }

        try {
            Log::info('Appointment routing geocoding address', [
                'trace' => $debugTrace,
                'address' => $address,
            ]);

            $response = Http::timeout(10)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'User-Agent' => 'CorzinDoctorRouting/1.0',
                ])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $address,
                    'format' => 'jsonv2',
                    'limit' => 1,
                ]);

            if (! $response->successful()) {
                Log::warning('Appointment routing geocode request failed', [
                    'trace' => $debugTrace,
                    'address' => $address,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $rows = $response->json();
            if (! is_array($rows) || empty($rows[0])) {
                Log::warning('Appointment routing geocode returned no rows', [
                    'trace' => $debugTrace,
                    'address' => $address,
                ]);
                return null;
            }

            $row = $rows[0];
            $latitude = isset($row['lat']) ? (float) $row['lat'] : null;
            $longitude = isset($row['lon']) ? (float) $row['lon'] : null;

            if ($latitude === null || $longitude === null) {
                Log::warning('Appointment routing geocode missing coordinates', [
                    'trace' => $debugTrace,
                    'address' => $address,
                    'row' => $row,
                ]);
                return null;
            }

            Log::info('Appointment routing geocode success', [
                'trace' => $debugTrace,
                'address' => $address,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'display_name' => $row['display_name'] ?? null,
            ]);

            return [
                'latitude' => $latitude,
                'longitude' => $longitude,
            ];
        } catch (\Throwable $exception) {
            Log::warning('Appointment routing geocode exception', [
                'trace' => $debugTrace,
                'address' => $address,
                'error' => $exception->getMessage(),
            ]);
            return null;
        }
    }
}
