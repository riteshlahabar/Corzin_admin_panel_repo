<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Doctor\Doctor;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DoctorListController extends Controller
{
    public function index()
    {
        $doctors = Doctor::latest()->get();
        $liveLocations = Doctor::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderByDesc('last_live_location_at')
            ->orderByDesc('updated_at')
            ->get();

        $summary = [
            'total' => $doctors->count(),
            'available' => $doctors->where('status', 'approved')->count(),
            'active' => $doctors->where('is_active_for_appointments', true)->count(),
            'locations' => $doctors->pluck('city')->filter()->unique()->count(),
        ];

        return view('doctor.index', compact('doctors', 'summary', 'liveLocations'));
    }

    public function create()
    {
        return view('doctor.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'clinic_name' => ['required', 'string', 'max:255'],
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
            'doctor_photo' => ['required', 'image', 'max:5120'],
            'adhar_document_front' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'adhar_document_back' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'pan_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'mmc_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'clinic_registration_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $doctor = Doctor::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'clinic_name' => $request->clinic_name,
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

        foreach (['pan_document', 'mmc_document', 'clinic_registration_document', 'doctor_photo'] as $field) {
            $doctor->{$field} = $this->storeDocument($request->file($field), $field, $doctor->id);
        }

        $doctor->save();

        return redirect()->route('doctor.index')->with('success', 'Doctor registered successfully.');
    }

    public function show(Doctor $doctor)
    {
        return view('doctor.show', compact('doctor'));
    }

    public function toggleApproval(Request $request, Doctor $doctor, FirebaseService $firebaseService)
    {
        $request->validate([
            'status' => ['required', Rule::in(['approved', 'pending'])],
        ]);

        $newStatus = $request->status;

        $doctor->update([
            'status' => $newStatus,
            'status_message' => $newStatus === 'approved' ? 'Approved by admin' : 'Marked as unapproved by admin',
            'approved_at' => $newStatus === 'approved' ? now() : null,
        ]);

        if ($newStatus === 'approved') {
            try {
                $firebaseService->sendToDevice(
                    $doctor->fcm_token,
                    'Doctor account approved',
                    'Your registration has been approved. You can now sign in.',
                    ['doctor_id' => (string) $doctor->id, 'status' => 'approved']
                );
            } catch (\Throwable $exception) {
            }
        }

        return redirect()->back()->with('success', 'Doctor status updated successfully.');
    }

    protected function storeDocument($file, string $field, int $doctorId): string
    {
        $directory = public_path('assets/doctor_registration_images');
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $filename = $doctorId.'_'.$field.'_'.Str::random(8).'.'.$file->getClientOriginalExtension();
        $file->move($directory, $filename);

        return 'assets/doctor_registration_images/'.$filename;
    }
}
