<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Doctor\DoctorAdminNotification;
use App\Models\Doctor\Doctor;
use App\Models\Doctor\DoctorAppointment;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class DoctorAppointmentController extends Controller
{
    public function __construct(protected FirebaseService $firebaseService)
    {
    }

    public function index(Request $request)
    {
        $rows = DoctorAppointment::query()
            ->with(['doctor', 'farmer'])
            ->latest('requested_at')
            ->latest()
            ->get();

        if ($request->filled('search')) {
            $search = strtolower(trim((string) $request->search));
            $rows = $rows->filter(function (DoctorAppointment $row) use ($search) {
                $doctorName = strtolower((string) optional($row->doctor)->full_name);
                $doctorAltName = strtolower((string) optional($row->doctor)->name);
                $farmerFullName = strtolower(trim(implode(' ', array_filter([
                    optional($row->farmer)->first_name,
                    optional($row->farmer)->middle_name,
                    optional($row->farmer)->last_name,
                ]))));
                $appointmentCode = strtolower((string) $row->appointment_code);

                return str_contains(strtolower((string) $row->farmer_name), $search)
                    || str_contains($farmerFullName, $search)
                    || str_contains(strtolower((string) $row->animal_name), $search)
                    || str_contains(strtolower((string) $row->concern), $search)
                    || str_contains($doctorName, $search)
                    || str_contains($doctorAltName, $search)
                    || str_contains($appointmentCode, $search);
            })->values();
        }

        $representative = $this->representativeAppointments($rows);

        $perPage = 20;
        $currentPage = max(1, (int) $request->query('page', 1));
        $total = $representative->count();
        $pageItems = $representative->forPage($currentPage, $perPage)->values();

        $appointments = new LengthAwarePaginator(
            $pageItems,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $summary = [
            'total' => $representative->count(),
            'pending' => $representative->where('status', 'pending')->count(),
            'approved' => $representative->whereIn('status', ['approved', 'scheduled'])->count(),
            'completed' => $representative->where('status', 'completed')->count(),
        ];

        $doctors = Doctor::query()
            ->where('status', 'approved')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return view('doctor.appointments', compact('appointments', 'summary', 'doctors'));
    }

    public function assignDoctor(Request $request, DoctorAppointment $appointment)
    {
        $data = $request->validate([
            'doctor_id' => ['required', 'exists:doctors,id'],
        ]);

        $doctorId = (int) $data['doctor_id'];
        $groupId = $appointment->appointment_group_id;

        $target = null;
        if (! empty($groupId)) {
            $groupRows = DoctorAppointment::query()
                ->where('appointment_group_id', $groupId)
                ->get();

            $target = $groupRows->first(fn (DoctorAppointment $row) => (int) $row->doctor_id === $doctorId);
            if (! $target) {
                $target = $appointment;
            }

            DoctorAppointment::query()
                ->where('appointment_group_id', $groupId)
                ->where('id', '!=', $target->id)
                ->update(['status' => 'cancelled']);
        } else {
            $target = $appointment;
        }

        $otpCode = (string) random_int(100000, 999999);
        $target->update([
            'doctor_id' => $doctorId,
            'status' => 'approved',
            'scheduled_at' => $target->scheduled_at ?? now(),
            'farmer_approved_at' => now(),
            'otp_code' => $otpCode,
            'otp_verified_at' => null,
        ]);
        $target->loadMissing(['doctor', 'farmer']);

        $token = optional($target->doctor)->fcm_token;
        $this->firebaseService->sendToDevice(
            $token,
            'Appointment Assigned by Admin',
            trim(($target->farmer_name ?: 'Farmer').' appointment has been assigned to you.'),
            [
                'type' => 'doctor_appointment',
                'event' => 'appointment_assigned_by_admin',
                'appointment_id' => (string) $target->id,
                'doctor_id' => (string) $target->doctor_id,
                'status' => (string) $target->status,
            ]
        );

        DoctorAdminNotification::create([
            'doctor_appointment_id' => $target->id,
            'event' => 'appointment_assigned_by_admin',
            'title' => 'Doctor assigned by admin',
            'message' => 'Appointment #'.$target->id.' assigned to doctor #'.$doctorId.'.',
            'is_read' => false,
        ]);
        $this->firebaseService->sendToWebAdmins(
            'Doctor assigned by admin',
            'Appointment #'.$target->id.' assigned to doctor #'.$doctorId.'.',
            [
                'type' => 'web_admin',
                'event' => 'appointment_assigned_by_admin',
                'appointment_id' => (string) $target->id,
                'doctor_id' => (string) $doctorId,
                'status' => (string) $target->status,
            ]
        );

        return back()->with('success', 'Doctor assigned and notified successfully.');
    }

    protected function representativeAppointments(Collection $rows): Collection
    {
        return $rows
            ->groupBy(function (DoctorAppointment $row) {
                if (! empty($row->appointment_group_id)) {
                    return $row->appointment_group_id;
                }

                $requestedKey = optional($row->requested_at ?: $row->created_at)?->format('Y-m-d H:i') ?? 'na';
                $farmerKey = (string) ($row->farmer_id ?: $row->farmer_phone ?: 'na');
                $animalKey = (string) ($row->animal_id ?: $row->animal_name ?: 'na');
                $concernKey = strtolower(trim((string) $row->concern));

                return sha1($farmerKey.'|'.$animalKey.'|'.$concernKey.'|'.$requestedKey);
            })
            ->map(function (Collection $group) {
                $hasCompleted = $group->contains(function (DoctorAppointment $row) {
                    return strtolower((string) $row->status) === 'completed';
                });
                if ($hasCompleted) {
                    return null;
                }

                $preferred = $group->first(function (DoctorAppointment $row) {
                    return in_array(strtolower((string) $row->status), [
                        'approved',
                        'scheduled',
                        'rescheduled',
                        'in_progress',
                        'completed',
                    ], true);
                });

                $selected = $preferred ?: $group->sortByDesc(function (DoctorAppointment $row) {
                    return optional($row->requested_at ?: $row->created_at)->timestamp ?? 0;
                })->first();

                return $selected;
            })
            ->filter()
            ->sortByDesc(function (DoctorAppointment $row) {
                return optional($row->requested_at ?: $row->created_at)->timestamp ?? 0;
            })
            ->values();
    }
}
