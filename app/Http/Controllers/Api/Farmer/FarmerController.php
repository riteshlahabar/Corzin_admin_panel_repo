<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Doctor\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class FarmerController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'mobile' => 'required|digits:10',
            'first_name' => 'required|string',
            'middle_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'village' => 'nullable|string',
            'city' => 'nullable|string',
            'taluka' => 'nullable|string',
            'district' => 'nullable|string',
            'state' => 'nullable|string',
            'pincode' => 'nullable|string',
            'farmer_photo' => 'nullable|image|max:5120',
            'referral_code' => 'nullable|string|max:40',
            'device_id' => 'nullable|string|max:120',
            'fcm_token' => 'nullable|string',
            'start_session' => 'nullable|boolean',
        ]);

        $referrerDoctor = $this->resolveReferrerDoctor($request->input('referral_code'));
        $sessionToken = $request->boolean('start_session') ? Str::random(64) : null;
        if ($request->filled('referral_code') && ! $referrerDoctor) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid doctor referral code. Please check and try again.',
            ], 422);
        }

        $farmer = DB::table('farmers')
            ->where('mobile', $request->mobile)
            ->first();

        /// UPDATE EXISTING FARMER
        if ($farmer) {
            $updates = [
                'first_name' => $request->first_name,
                'middle_name' => $request->middle_name,
                'last_name' => $request->last_name,
                'village' => $request->village,
                'city' => $request->city,
                'taluka' => $request->taluka,
                'district' => $request->district,
                'state' => $request->state,
                'pincode' => $request->pincode,
                'updated_at' => now(),
            ];
            if ($referrerDoctor && empty($farmer->referred_by_doctor_id)) {
                $updates['referred_by_doctor_id'] = $referrerDoctor->id;
                $updates['doctor_referral_code'] = $referrerDoctor->referral_code;
            }
            if ($sessionToken !== null) {
                $updates['active_device_id'] = $request->input('device_id');
                $updates['active_session_token'] = $sessionToken;
                $updates['active_session_at'] = now();
                if ($request->filled('fcm_token')) {
                    $updates['fcm_token'] = $request->input('fcm_token');
                }
            }

            DB::table('farmers')
                ->where('mobile', $request->mobile)
                ->update($updates);

            if ($request->hasFile('farmer_photo')) {
                $photoPath = $this->storeFarmerPhoto(
                    $request->file('farmer_photo'),
                    (int) $farmer->id,
                    $farmer->farmer_photo
                );

                DB::table('farmers')
                    ->where('id', $farmer->id)
                    ->update([
                        'farmer_photo' => $photoPath,
                        'updated_at' => now(),
                    ]);
            }

            $updatedFarmer = DB::table('farmers')
                ->where('mobile', $request->mobile)
                ->first();

            return response()->json([
                'status' => true,
                'message' => 'Farmer updated successfully',
                'is_registered' => true,
                'farmer_name' => $updatedFarmer->first_name ?? '',
                'session_token' => $sessionToken ?: ($updatedFarmer->active_session_token ?? ''),
                'data' => $this->transformFarmer($updatedFarmer),
            ], 200);
        }

        /// CREATE NEW FARMER
        $farmerId = DB::table('farmers')->insertGetId([
            'mobile' => $request->mobile,
            'referred_by_doctor_id' => $referrerDoctor?->id,
            'doctor_referral_code' => $referrerDoctor?->referral_code,
            'referral_reward_granted_at' => null,
            'fcm_token' => $request->input('fcm_token'),
            'active_device_id' => $sessionToken !== null ? $request->input('device_id') : null,
            'active_session_token' => $sessionToken,
            'active_session_at' => $sessionToken !== null ? now() : null,
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'village' => $request->village,
            'city' => $request->city,
            'taluka' => $request->taluka,
            'district' => $request->district,
            'state' => $request->state,
            'pincode' => $request->pincode,
            'farmer_photo' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($request->hasFile('farmer_photo')) {
            $photoPath = $this->storeFarmerPhoto($request->file('farmer_photo'), (int) $farmerId);

            DB::table('farmers')
                ->where('id', $farmerId)
                ->update([
                    'farmer_photo' => $photoPath,
                    'updated_at' => now(),
                ]);
        }

        $newFarmer = DB::table('farmers')
            ->where('id', $farmerId)
            ->first();

        return response()->json([
            'status' => true,
            'message' => 'Farmer created successfully',
            'is_registered' => true,
            'farmer_name' => $newFarmer->first_name ?? '',
            'session_token' => $sessionToken ?: '',
            'data' => $this->transformFarmer($newFarmer),
        ], 201);
    }

    public function getProfileByMobile($mobile)
    {
        $farmer = DB::table('farmers')
            ->where('mobile', $mobile)
            ->first();

        if (! $farmer) {
            return response()->json([
                'status' => false,
                'message' => 'Farmer not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Farmer profile fetched successfully',
            'data' => $this->transformFarmer($farmer),
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'first_name' => 'nullable|string',
            'middle_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'village' => 'nullable|string',
            'city' => 'nullable|string',
            'taluka' => 'nullable|string',
            'district' => 'nullable|string',
            'state' => 'nullable|string',
            'pincode' => 'nullable|string',
            'farmer_photo' => 'nullable|image|max:5120',
        ]);

        $farmer = DB::table('farmers')->where('id', $id)->first();

        if (! $farmer) {
            return response()->json([
                'status' => false,
                'message' => 'Farmer not found'
            ], 404);
        }

        $photoPath = $farmer->farmer_photo;
        if ($request->hasFile('farmer_photo')) {
            $photoPath = $this->storeFarmerPhoto(
                $request->file('farmer_photo'),
                (int) $farmer->id,
                $farmer->farmer_photo
            );
        }

        DB::table('farmers')->where('id', $id)->update([
            'first_name' => $request->first_name ?? $farmer->first_name,
            'middle_name' => $request->middle_name ?? $farmer->middle_name,
            'last_name' => $request->last_name ?? $farmer->last_name,
            'village' => $request->village ?? $farmer->village,
            'city' => $request->city ?? $farmer->city,
            'taluka' => $request->taluka ?? $farmer->taluka,
            'district' => $request->district ?? $farmer->district,
            'state' => $request->state ?? $farmer->state,
            'pincode' => $request->pincode ?? $farmer->pincode,
            'farmer_photo' => $photoPath,
            'updated_at' => now(),
        ]);

        $updatedFarmer = DB::table('farmers')->where('id', $id)->first();

        return response()->json([
            'status' => true,
            'message' => 'Farmer updated successfully',
            'data' => $this->transformFarmer($updatedFarmer),
        ], 200);
    }


    public function updateFcmToken(Request $request, $id)
    {
        $request->validate([
            'fcm_token' => 'required|string',
            'device_id' => 'nullable|string|max:120',
            'session_token' => 'nullable|string|max:120',
        ]);

        $farmer = DB::table('farmers')->where('id', $id)->first();

        if (! $farmer) {
            return response()->json([
                'status' => false,
                'message' => 'Farmer not found',
            ], 404);
        }

        $activeToken = trim((string) ($farmer->active_session_token ?? ''));
        $requestToken = trim((string) $request->input('session_token', ''));
        if ($activeToken !== '' && ($requestToken === '' || ! hash_equals($activeToken, $requestToken))) {
            return response()->json([
                'status' => false,
                'message' => 'This account is logged in on another device.',
                'force_logout' => true,
            ], 401);
        }

        $updates = [
            'fcm_token' => $request->fcm_token,
            'updated_at' => now(),
        ];
        if ($request->filled('device_id')) {
            $updates['active_device_id'] = $request->input('device_id');
        }

        DB::table('farmers')->where('id', $id)->update($updates);

        return response()->json([
            'status' => true,
            'message' => 'FCM token updated successfully.',
        ]);
    }

    public function updateCurrentLocation(Request $request, $id)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $farmer = DB::table('farmers')->where('id', $id)->first();
        if (! $farmer) {
            return response()->json([
                'status' => false,
                'message' => 'Farmer not found',
            ], 404);
        }

        $latitude = (float) $request->latitude;
        $longitude = (float) $request->longitude;
        $currentLocationAddress = $this->resolveAddressFromCoordinates($latitude, $longitude);

        DB::table('farmers')->where('id', $id)->update([
            'latitude' => $latitude,
            'longitude' => $longitude,
            'current_location_address' => $currentLocationAddress,
            'updated_at' => now(),
        ]);

        $updatedFarmer = DB::table('farmers')->where('id', $id)->first();

        return response()->json([
            'status' => true,
            'message' => 'Current location updated successfully.',
            'data' => $this->transformFarmer($updatedFarmer),
        ], 200);
    }

    private function storeFarmerPhoto($file, int $farmerId, ?string $oldPhoto = null): string
    {
        $directory = public_path('assets/farmer_photo');
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        if (! empty($oldPhoto)) {
            $oldPhotoPath = public_path($oldPhoto);
            if (File::exists($oldPhotoPath)) {
                File::delete($oldPhotoPath);
            }
        }

        $filename = $farmerId.'_farmer_photo_'.time().'.'.$file->getClientOriginalExtension();
        $file->move($directory, $filename);

        return 'assets/farmer_photo/'.$filename;
    }

    private function transformFarmer($farmer): array
    {
        return [
            'id' => $farmer->id ?? null,
            'mobile' => $farmer->mobile ?? '',
            'first_name' => $farmer->first_name ?? '',
            'middle_name' => $farmer->middle_name ?? '',
            'last_name' => $farmer->last_name ?? '',
            'village' => $farmer->village ?? '',
            'city' => $farmer->city ?? '',
            'taluka' => $farmer->taluka ?? '',
            'district' => $farmer->district ?? '',
            'state' => $farmer->state ?? '',
            'pincode' => $farmer->pincode ?? '',
            'latitude' => isset($farmer->latitude) ? (string) $farmer->latitude : '',
            'longitude' => isset($farmer->longitude) ? (string) $farmer->longitude : '',
            'current_location_address' => $farmer->current_location_address ?? '',
            'farmer_photo' => $farmer->farmer_photo ?? '',
            'farmer_photo_url' => ! empty($farmer->farmer_photo) ? asset($farmer->farmer_photo) : '',
            'referred_by_doctor_id' => $farmer->referred_by_doctor_id ?? null,
            'doctor_referral_code' => $farmer->doctor_referral_code ?? '',
            'referral_reward_granted_at' => $farmer->referral_reward_granted_at ?? null,
            'created_at' => $farmer->created_at ?? null,
            'updated_at' => $farmer->updated_at ?? null,
        ];
    }

    private function resolveReferrerDoctor(?string $code): ?Doctor
    {
        $normalized = strtoupper(trim((string) $code));
        if ($normalized === '') {
            return null;
        }

        return Doctor::query()
            ->whereRaw('UPPER(referral_code) = ?', [$normalized])
            ->where('status', 'approved')
            ->first();
    }

    private function resolveAddressFromCoordinates(float $latitude, float $longitude): string
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'User-Agent' => 'CorzinFarmerLocation/1.0',
                ])
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'format' => 'jsonv2',
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'zoom' => 18,
                    'addressdetails' => 1,
                ]);

            if (! $response->successful()) {
                return 'Lat: '.$latitude.', Lng: '.$longitude;
            }

            $row = $response->json();
            if (! is_array($row)) {
                return 'Lat: '.$latitude.', Lng: '.$longitude;
            }

            $displayName = trim((string) ($row['display_name'] ?? ''));
            return $displayName !== '' ? $displayName : 'Lat: '.$latitude.', Lng: '.$longitude;
        } catch (\Throwable $exception) {
            return 'Lat: '.$latitude.', Lng: '.$longitude;
        }
    }
}

