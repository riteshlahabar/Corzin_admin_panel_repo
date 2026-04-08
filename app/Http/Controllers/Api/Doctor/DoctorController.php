<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Doctor\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class DoctorController extends Controller
{
    public function index(Request $request)
    {
        $query = Doctor::query()
            ->where('status', 'approved')
            ->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('speciality', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $doctors = $query->get()->map(function (Doctor $doctor) {
            return [
                'id' => $doctor->id,
                'name' => $doctor->full_name ?: $doctor->name,
                'speciality' => $doctor->degree ?: $doctor->speciality,
                'location' => $doctor->city ?: $doctor->location,
                'phone' => $doctor->contact_number ?: $doctor->phone,
                'experience' => $doctor->experience,
                'available_today' => $doctor->available_today,
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
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'dob' => ['required', 'date'],
            'degree' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:255', Rule::unique('doctors', 'email')],
            'adhar_number' => ['required', 'string', 'max:50'],
            'pan_number' => ['required', 'string', 'max:50'],
            'mmc_registration_number' => ['required', 'string', 'max:100'],
            'clinic_registration_number' => ['required', 'string', 'max:100'],
            'clinic_address' => ['required', 'string'],
            'village' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'taluka' => ['required', 'string', 'max:255'],
            'district' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'pincode' => ['required', 'string', 'max:15'],
            'password' => ['required', 'confirmed', 'min:8'],
            'terms_accepted' => ['required', 'accepted'],
            'adhar_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'pan_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'mmc_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'clinic_registration_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'doctor_photo' => ['required', 'image', 'max:5120'],
        ]);

        $doctor = Doctor::create([
            'name' => trim($request->first_name.' '.$request->middle_name.' '.$request->last_name),
            'speciality' => $request->degree,
            'location' => $request->city,
            'phone' => $request->contact_number,
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'dob' => $request->dob,
            'degree' => $request->degree,
            'contact_number' => $request->contact_number,
            'email' => $request->email,
            'adhar_number' => $request->adhar_number,
            'pan_number' => $request->pan_number,
            'mmc_registration_number' => $request->mmc_registration_number,
            'clinic_registration_number' => $request->clinic_registration_number,
            'clinic_address' => $request->clinic_address,
            'village' => $request->village,
            'city' => $request->city,
            'taluka' => $request->taluka,
            'district' => $request->district,
            'state' => $request->state,
            'pincode' => $request->pincode,
            'password' => $request->password,
            'terms_accepted' => true,
            'terms_text' => 'Doctor agrees to all onboarding verification and approval conditions.',
            'status' => 'pending',
            'available_today' => false,
        ]);

        foreach (['adhar_document', 'pan_document', 'mmc_document', 'clinic_registration_document', 'doctor_photo'] as $field) {
            $doctor->{$field} = $this->storeDocument($request->file($field), $field, $doctor->id);
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

    public function profile(Doctor $doctor)
    {
        return response()->json([
            'status' => true,
            'message' => 'Doctor profile fetched successfully.',
            'data' => $this->doctorPayload($doctor),
        ]);
    }

    protected function doctorPayload(Doctor $doctor): array
    {
        return [
            'id' => $doctor->id,
            'first_name' => $doctor->first_name,
            'middle_name' => $doctor->middle_name,
            'last_name' => $doctor->last_name,
            'dob' => optional($doctor->dob)->format('Y-m-d') ?? $doctor->dob,
            'degree' => $doctor->degree,
            'contact_number' => $doctor->contact_number,
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
            'terms_text' => $doctor->terms_text,
            'doctor_photo_url' => $doctor->doctorPhotoUrl(),
            'documents' => array_filter($doctor->documents()),
        ];
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
}
