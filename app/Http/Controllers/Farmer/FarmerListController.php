<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farmer\Farmer;
use App\Models\Farmer\FarmerSetting;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FarmerListController extends Controller
{
    public function __construct(protected FirebaseService $firebaseService)
    {
    }

    public function index()
    {
        $farmers = Farmer::latest()->get();

        return view('farmer.index', compact('farmers'));
    }

    public function create()
    {
        return view('farmer.form', [
            'farmer' => new Farmer(),
            'formTitle' => 'Add Farmer',
            'formAction' => route('farmer.store'),
            'isEdit' => false,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateFarmer($request);
        $data['is_active'] = $request->boolean('is_active', true);

        Farmer::create($data);

        return redirect()->route('farmer.list')->with('success', 'Farmer added successfully.');
    }

    public function edit(Farmer $farmer)
    {
        return view('farmer.form', [
            'farmer' => $farmer,
            'formTitle' => 'Edit Farmer',
            'formAction' => route('farmer.update', $farmer),
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, Farmer $farmer)
    {
        $wasActive = (bool) $farmer->is_active;
        $data = $this->validateFarmer($request, $farmer->id);
        $data['is_active'] = $request->boolean('is_active');

        $farmer->update($data);

        if ($wasActive && ! (bool) $farmer->is_active) {
            $this->notifyAndForceLogoutInactiveFarmer($farmer->fresh());
        }

        return redirect()->route('farmer.list')->with('success', 'Farmer updated successfully.');
    }

    public function toggle(Farmer $farmer)
    {
        $nextActive = ! (bool) $farmer->is_active;
        $farmer->update([
            'is_active' => $nextActive,
        ]);

        if (! $nextActive) {
            $this->notifyAndForceLogoutInactiveFarmer($farmer->fresh());
        }

        return redirect()->route('farmer.list')->with('success', 'Farmer status updated successfully.');
    }

    private function validateFarmer(Request $request, ?int $farmerId = null): array
    {
        return $request->validate([
            'mobile' => ['required', 'string', 'max:20', Rule::unique('farmers', 'mobile')->ignore($farmerId)],
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'village' => 'required|string|max:255',
            'city' => 'nullable|string|max:255',
            'taluka' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'pincode' => 'nullable|string|max:20',
        ]);
    }

    private function notifyAndForceLogoutInactiveFarmer(Farmer $farmer): void
    {
        $setting = FarmerSetting::query()->first();
        $adminNumber = trim((string) ($setting->admin_contact_number ?? ''));
        $message = $adminNumber !== ''
            ? "Your account is inactive. Please contact admin: {$adminNumber}"
            : 'Your account is inactive. Please contact admin.';

        $farmer->update([
            'active_device_id' => null,
            'active_session_token' => null,
            'active_session_at' => null,
        ]);

        $this->firebaseService->sendToDevice(
            $farmer->fcm_token,
            'Account Inactive',
            $message,
            [
                'type' => 'force_logout',
                'event' => 'force_logout',
                'reason' => 'account_inactive',
                'message' => $message,
                'admin_number' => $adminNumber,
            ]
        );
    }
}
