<?php

namespace App\Http\Controllers\Api\DoctorApp;

use App\Http\Controllers\Controller;
use App\Models\Doctor\Doctor;
use App\Models\Doctor\DoctorAppointment;
use App\Services\FirebaseService;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

class DoctorAppController extends Controller
{
    public function index(Request $request)
    {
        $query = Doctor::query()
            ->where('status', 'approved')
            ->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($builder) use ($search) {
                $builder->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('degree', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('contact_number', 'like', "%{$search}%")
                    ->orWhere('whatsapp_number', 'like', "%{$search}%");
            });
        }

        $doctors = $query->get()->map(function (Doctor $doctor) {
            return [
                'id' => $doctor->id,
                'name' => $doctor->full_name,
                'speciality' => $doctor->degree,
                'location' => $doctor->city,
                'phone' => $doctor->contact_number,
                'experience' => null,
                'available_today' => (bool) ($doctor->status === 'approved' && $doctor->is_active_for_appointments),
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Doctors fetched successfully',
            'data' => $doctors,
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'clinic_name' => ['nullable', 'string', 'max:255'],
            'degree' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'string', 'max:30'],
            'whatsapp_number' => ['nullable', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:255', Rule::unique('doctors', 'email')],
            'adhar_number' => ['required', 'string', 'max:50'],
            'pan_number' => ['required', 'string', 'max:50'],
            'mmc_registration_number' => ['required', 'string', 'max:100'],
            'clinic_registration_number' => ['nullable', 'string', 'max:100'],
            'clinic_address' => ['nullable', 'string'],
            'village' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'taluka' => ['required', 'string', 'max:255'],
            'district' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'pincode' => ['required', 'string', 'max:15'],
            'password' => ['required', 'confirmed', 'min:8'],
            'terms_accepted' => ['required', 'accepted'],
            'adhar_document_front' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'adhar_document_back' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'pan_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'mmc_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'clinic_registration_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'doctor_photo' => ['required', 'image', 'max:5120'],
        ]);

        $resolvedCity = trim((string) ($request->city ?? ''));
        if ($resolvedCity === '') {
            $resolvedCity = trim((string) $request->taluka);
        }

        $clinicName = trim((string) $request->clinic_name);
        if ($clinicName === '') {
            $clinicName = null;
        }
        $whatsappNumber = trim((string) $request->whatsapp_number);
        if ($whatsappNumber === '') {
            $whatsappNumber = trim((string) $request->contact_number);
        }

        $doctor = Doctor::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'clinic_name' => $clinicName,
            'degree' => $request->degree,
            'contact_number' => $request->contact_number,
            'whatsapp_number' => $whatsappNumber,
            'email' => $request->email,
            'adhar_number' => $request->adhar_number,
            'pan_number' => $request->pan_number,
            'mmc_registration_number' => $request->mmc_registration_number,
            'clinic_registration_number' => trim((string) $request->clinic_registration_number),
            'clinic_address' => trim((string) $request->clinic_address),
            'village' => $request->village,
            'city' => $resolvedCity,
            'taluka' => $request->taluka,
            'district' => $request->district,
            'state' => $request->state,
            'pincode' => $request->pincode,
            'password' => $request->password,
            'terms_accepted' => true,
            'terms_text' => 'Doctor agrees to all onboarding verification and approval conditions.',
            'status' => 'pending',
            // Keep insert valid for NOT NULL doc columns, then overwrite with real files below.
            'adhar_document' => '',
            'adhar_document_back' => '',
            'pan_document' => '',
            'mmc_document' => '',
            'clinic_registration_document' => '',
            'doctor_photo' => '',
        ]);

        $doctor->adhar_document = $this->storeDocument(
            $request->file('adhar_document_front'),
            'adhar_document_front',
            $doctor->id
        );
        $doctor->adhar_document_back = $this->storeDocument(
            $request->file('adhar_document_back'),
            'adhar_document_back',
            $doctor->id
        );
        foreach (['pan_document', 'mmc_document', 'doctor_photo'] as $field) {
            $doctor->{$field} = $this->storeDocument($request->file($field), $field, $doctor->id);
        }

        if ($request->hasFile('clinic_registration_document')) {
            $doctor->clinic_registration_document = $this->storeDocument(
                $request->file('clinic_registration_document'),
                'clinic_registration_document',
                $doctor->id
            );
        }

        $doctor->save();

        return response()->json([
            'status' => true,
            'message' => 'Registration submitted successfully. Please wait for admin approval.',
            'data' => [
                'id' => $doctor->id,
                'status' => $doctor->status,
            ],
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $doctor = Doctor::where('email', $credentials['email'])->first();

        if (! $doctor || ! Hash::check($credentials['password'], $doctor->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid email or password.',
            ], 422);
        }

        if ($doctor->status !== 'approved') {
            return response()->json([
                'status' => false,
                'message' => 'Your account is not approved yet.',
                'data' => [
                    'status' => $doctor->status,
                ],
            ], 403);
        }

        return response()->json([
            'status' => true,
            'message' => 'Login successful.',
            'data' => $this->doctorPayload($doctor),
        ]);
    }

    public function forgotPassword(Request $request)
    {
        return response()->json([
            'status' => false,
            'message' => 'Please verify Firebase OTP before resetting password.',
        ], 422);
    }

    public function forgotPasswordLookup(Request $request)
    {
        $payload = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $doctor = Doctor::where('email', $payload['email'])->first();

        if (! $doctor) {
            return response()->json([
                'status' => false,
                'message' => 'Doctor account not found for this email.',
            ], 404);
        }

        $mobile = $this->doctorResetMobile($doctor);
        if ($mobile === null) {
            return response()->json([
                'status' => false,
                'message' => 'Mobile number is not available for this doctor account.',
            ], 422);
        }

        return response()->json([
            'status' => true,
            'message' => 'Doctor mobile number fetched successfully.',
            'data' => [
                'email' => $doctor->email,
                'mobile_number' => $mobile,
                'firebase_phone_number' => '+91'.$mobile,
            ],
        ]);
    }

    public function resetForgotPasswordWithFirebase(Request $request, FirebaseService $firebaseService)
    {
        $payload = $request->validate([
            'email' => ['required', 'email'],
            'firebase_id_token' => ['required', 'string'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $doctor = Doctor::where('email', $payload['email'])->first();

        if (! $doctor) {
            return response()->json([
                'status' => false,
                'message' => 'Doctor account not found for this email.',
            ], 404);
        }

        try {
            $verifiedToken = $firebaseService->verifyToken($payload['firebase_id_token']);
            $firebasePhone = (string) ($verifiedToken->claims()->get('phone_number') ?? '');
        } catch (\Throwable $exception) {
            return response()->json([
                'status' => false,
                'message' => 'Firebase OTP verification failed. Please send OTP again.',
            ], 401);
        }

        if (! $this->firebasePhoneMatchesDoctor($firebasePhone, $doctor)) {
            return response()->json([
                'status' => false,
                'message' => 'Verified mobile number does not match this doctor account.',
            ], 422);
        }

        $doctor->update([
            'password' => $payload['password'],
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Password reset successful. Please sign in with your new password.',
        ]);
    }

    public function reports(Request $request, Doctor $doctor)
    {
        $data = $request->validate([
            'tab' => ['nullable', Rule::in(['earnings', 'clients'])],
            'date' => ['nullable', 'date'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $tab = strtolower((string) ($data['tab'] ?? 'earnings'));
        $search = trim((string) ($data['search'] ?? ''));
        $selectedDate = ! empty($data['date'])
            ? Carbon::parse((string) $data['date'])
            : now();
        $start = $selectedDate->copy()->startOfDay();
        $end = $selectedDate->copy()->endOfDay();

        $dateExpr = DB::raw('COALESCE(requested_at, created_at)');
        $completedDateExpr = DB::raw('COALESCE(completed_at, requested_at, created_at)');

        $summaryTotalRequest = DoctorAppointment::query()
            ->where('doctor_id', $doctor->id)
            ->whereBetween($dateExpr, [$start, $end])
            ->count();

        $summaryTotalEarning = (float) DoctorAppointment::query()
            ->where('doctor_id', $doctor->id)
            ->where('status', 'completed')
            ->whereBetween($completedDateExpr, [$start, $end])
            ->sum(DB::raw('COALESCE(charges, 0)'));

        $summaryMedicationCost = (float) DoctorAppointment::query()
            ->where('doctor_id', $doctor->id)
            ->where('status', 'completed')
            ->whereBetween($completedDateExpr, [$start, $end])
            ->sum(DB::raw('COALESCE(on_site_medicine_charges, 0)'));

        if ($tab === 'clients') {
            $clientsQuery = DoctorAppointment::query()
                ->where('doctor_id', $doctor->id)
                ->whereBetween($dateExpr, [$start, $end]);

            if ($search !== '') {
                $clientsQuery->where(function ($query) use ($search) {
                    $query->where('farmer_name', 'like', "%{$search}%")
                        ->orWhere('farmer_phone', 'like', "%{$search}%")
                        ->orWhere('animal_name', 'like', "%{$search}%")
                        ->orWhere('concern', 'like', "%{$search}%");
                });
            }

            $items = $clientsQuery
                ->selectRaw('COALESCE(NULLIF(farmer_name, ""), "Farmer") as farmer_name')
                ->selectRaw('COALESCE(NULLIF(farmer_phone, ""), "-") as farmer_phone')
                ->selectRaw('COUNT(*) as total_requests')
                ->selectRaw('COALESCE(SUM(COALESCE(charges, 0)), 0) as total_earning')
                ->selectRaw('COALESCE(SUM(COALESCE(on_site_medicine_charges, 0)), 0) as medication_cost')
                ->selectRaw('MAX(COALESCE(completed_at, requested_at, created_at)) as last_activity_at')
                ->groupBy('farmer_name', 'farmer_phone')
                ->orderByDesc('last_activity_at')
                ->limit(500)
                ->get()
                ->map(function ($row) {
                    return [
                        'farmer_name' => (string) ($row->farmer_name ?? 'Farmer'),
                        'farmer_phone' => (string) ($row->farmer_phone ?? '-'),
                        'total_requests' => (int) ($row->total_requests ?? 0),
                        'total_earning' => (float) ($row->total_earning ?? 0),
                        'medication_cost' => (float) ($row->medication_cost ?? 0),
                        'last_activity_at' => ! empty($row->last_activity_at)
                            ? Carbon::parse((string) $row->last_activity_at)->toIso8601String()
                            : null,
                    ];
                })
                ->values();
        } else {
            $earningsQuery = DoctorAppointment::query()
                ->where('doctor_id', $doctor->id)
                ->where('status', 'completed')
                ->whereBetween($completedDateExpr, [$start, $end]);

            if ($search !== '') {
                $earningsQuery->where(function ($query) use ($search) {
                    $query->where('farmer_name', 'like', "%{$search}%")
                        ->orWhere('farmer_phone', 'like', "%{$search}%")
                        ->orWhere('animal_name', 'like', "%{$search}%")
                        ->orWhere('concern', 'like', "%{$search}%");
                });
            }

            $items = $earningsQuery
                ->latest('completed_at')
                ->latest()
                ->limit(500)
                ->get()
                ->map(function (DoctorAppointment $row) {
                    return [
                        'appointment_id' => $row->id,
                        'appointment_code' => $row->appointment_code,
                        'farmer_name' => (string) ($row->farmer_name ?? ''),
                        'farmer_phone' => (string) ($row->farmer_phone ?? ''),
                        'animal_name' => (string) ($row->animal_name ?? ''),
                        'concern' => (string) ($row->concern ?? ''),
                        'status' => (string) ($row->status ?? ''),
                        'total_earning' => (float) ($row->charges ?? 0),
                        'medication_cost' => (float) ($row->on_site_medicine_charges ?? 0),
                        'completed_at' => optional($row->completed_at ?? $row->requested_at ?? $row->created_at)
                            ->toIso8601String(),
                    ];
                })
                ->values();
        }

        return response()->json([
            'status' => true,
            'message' => 'Doctor report fetched successfully.',
            'data' => [
                'tab' => $tab,
                'date' => $selectedDate->toDateString(),
                'summary' => [
                    'total_request' => (int) $summaryTotalRequest,
                    'total_earning' => round($summaryTotalEarning, 2),
                    'medication_cost' => round($summaryMedicationCost, 2),
                ],
                'items' => $items,
            ],
        ]);
    }

    public function profile(Doctor $doctor)
    {
        return response()->json([
            'status' => true,
            'message' => 'Doctor profile fetched successfully.',
            'data' => $this->doctorPayload($doctor),
        ]);
    }

    public function updateProfile(Request $request, Doctor $doctor)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'clinic_name' => ['nullable', 'string', 'max:255'],
            'degree' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'string', 'max:30'],
            'whatsapp_number' => ['nullable', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:255', Rule::unique('doctors', 'email')->ignore($doctor->id)],
            'adhar_number' => ['required', 'string', 'max:50'],
            'pan_number' => ['required', 'string', 'max:50'],
            'mmc_registration_number' => ['required', 'string', 'max:100'],
            'clinic_registration_number' => ['required', 'string', 'max:100'],
            'clinic_address' => ['required', 'string'],
            'village' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'taluka' => ['required', 'string', 'max:255'],
            'district' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'pincode' => ['required', 'string', 'max:15'],
            'doctor_photo' => ['nullable', 'image', 'max:5120'],
            'adhar_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'pan_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'mmc_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'clinic_registration_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $resolvedCity = trim((string) ($data['city'] ?? ''));
        if ($resolvedCity === '') {
            $resolvedCity = trim((string) $data['taluka']);
        }
        $clinicName = trim((string) ($data['clinic_name'] ?? ''));
        if ($clinicName === '') {
            $clinicName = null;
        }
        $whatsappNumber = trim((string) ($data['whatsapp_number'] ?? ''));
        if ($whatsappNumber === '') {
            $whatsappNumber = trim((string) $data['contact_number']);
        }

        $doctor->update([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'clinic_name' => $clinicName,
            'degree' => $data['degree'],
            'contact_number' => $data['contact_number'],
            'whatsapp_number' => $whatsappNumber,
            'email' => $data['email'],
            'adhar_number' => $data['adhar_number'],
            'pan_number' => $data['pan_number'],
            'mmc_registration_number' => $data['mmc_registration_number'],
            'clinic_registration_number' => $data['clinic_registration_number'],
            'clinic_address' => $data['clinic_address'],
            'village' => $data['village'],
            'city' => $resolvedCity,
            'taluka' => $data['taluka'],
            'district' => $data['district'],
            'state' => $data['state'],
            'pincode' => $data['pincode'],
        ]);

        foreach (['adhar_document', 'pan_document', 'mmc_document', 'clinic_registration_document', 'doctor_photo'] as $field) {
            if ($request->hasFile($field)) {
                $doctor->{$field} = $this->storeDocument($request->file($field), $field, $doctor->id);
            }
        }
        $doctor->save();

        return response()->json([
            'status' => true,
            'message' => 'Doctor profile updated successfully.',
            'data' => $this->doctorPayload($doctor->fresh()),
        ]);
    }

    public function updateFcmToken(Request $request, Doctor $doctor)
    {
        $request->validate([
            'fcm_token' => ['required', 'string'],
        ]);

        $doctor->update([
            'fcm_token' => $request->fcm_token,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'FCM token updated successfully.',
        ]);
    }

    public function updateAvailability(Request $request, Doctor $doctor)
    {
        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $doctor->is_active_for_appointments = (bool) $data['is_active'];
        $doctor->save();

        return response()->json([
            'status' => true,
            'message' => $doctor->is_active_for_appointments
                ? 'Doctor marked active for appointments.'
                : 'Doctor marked inactive for appointments.',
            'data' => $this->doctorPayload($doctor->fresh()),
        ]);
    }

    public function updateLiveLocation(Request $request, Doctor $doctor)
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        if (! $doctor->is_active_for_appointments) {
            return response()->json([
                'status' => false,
                'message' => 'Doctor is inactive. Enable Active mode to update live location.',
            ], 422);
        }

        $doctor->latitude = $data['latitude'];
        $doctor->longitude = $data['longitude'];
        $doctor->last_live_location_at = now();
        $doctor->live_location_address = $this->resolveLiveLocationAddress(
            (float) $data['latitude'],
            (float) $data['longitude']
        );
        $doctor->save();

        return response()->json([
            'status' => true,
            'message' => 'Doctor live location updated successfully.',
            'data' => $this->doctorPayload($doctor->fresh()),
        ]);
    }

    protected function doctorPayload(Doctor $doctor): array
    {
        return [
            'id' => $doctor->id,
            'first_name' => $doctor->first_name,
            'last_name' => $doctor->last_name,
            'clinic_name' => $doctor->clinic_name,
            'degree' => $doctor->degree,
            'contact_number' => $doctor->contact_number,
            'whatsapp_number' => $doctor->whatsapp_number,
            'email' => $doctor->email,
            'adhar_number' => $doctor->adhar_number,
            'pan_number' => $doctor->pan_number,
            'mmc_registration_number' => $doctor->mmc_registration_number,
            'clinic_registration_number' => $doctor->clinic_registration_number,
            'clinic_address' => $doctor->clinic_address,
            'village' => $doctor->village,
            'city' => $doctor->city,
            'taluka' => $doctor->taluka,
            'district' => $doctor->district,
            'state' => $doctor->state,
            'pincode' => $doctor->pincode,
            'status' => $doctor->status,
            'is_active_for_appointments' => (bool) $doctor->is_active_for_appointments,
            'latitude' => $doctor->latitude !== null ? (float) $doctor->latitude : null,
            'longitude' => $doctor->longitude !== null ? (float) $doctor->longitude : null,
            'last_live_location_at' => optional($doctor->last_live_location_at)->toIso8601String(),
            'live_location_address' => $doctor->live_location_address,
            'terms_text' => $doctor->terms_text,
            'doctor_photo_url' => $doctor->doctorPhotoUrl(),
            'documents' => array_filter($doctor->documents()),
        ];
    }

    protected function resolveLiveLocationAddress(float $latitude, float $longitude): string
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'User-Agent' => 'CorzinDoctorAdmin/1.0',
                ])
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'format' => 'jsonv2',
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'zoom' => 18,
                    'addressdetails' => 1,
                ]);

            if ($response->successful()) {
                $displayName = trim((string) data_get($response->json(), 'display_name', ''));
                if ($displayName !== '') {
                    return $displayName;
                }
            }
        } catch (\Throwable $exception) {
        }

        return 'Lat: '.number_format($latitude, 6).', Lng: '.number_format($longitude, 6);
    }

    protected function storeDocument($file, string $field, int $doctorId): string
    {
        $directory = public_path('assets/doctor_registration_images');
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $filename = $doctorId.'_'.$field.'_'.time().'.'.$file->getClientOriginalExtension();
        $file->move($directory, $filename);

        return 'assets/doctor_registration_images/'.$filename;
    }

    protected function doctorResetMobile(Doctor $doctor): ?string
    {
        foreach ([$doctor->contact_number, $doctor->whatsapp_number] as $mobile) {
            $normalized = $this->normalizeIndianMobile($mobile);
            if ($normalized !== null) {
                return $normalized;
            }
        }

        return null;
    }

    protected function firebasePhoneMatchesDoctor(string $firebasePhone, Doctor $doctor): bool
    {
        $firebaseMobile = $this->normalizeIndianMobile($firebasePhone);
        if ($firebaseMobile === null) {
            return false;
        }

        $doctorMobiles = [
            $this->normalizeIndianMobile($doctor->contact_number),
            $this->normalizeIndianMobile($doctor->whatsapp_number),
        ];

        return in_array($firebaseMobile, array_filter($doctorMobiles), true);
    }

    protected function normalizeIndianMobile(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);
        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            $digits = substr($digits, 2);
        }
        if (strlen($digits) > 10) {
            $digits = substr($digits, -10);
        }

        return strlen($digits) === 10 ? $digits : null;
    }

    protected function maskMobileNumber(string $mobile): string
    {
        return substr($mobile, 0, 2).'******'.substr($mobile, -2);
    }
}
