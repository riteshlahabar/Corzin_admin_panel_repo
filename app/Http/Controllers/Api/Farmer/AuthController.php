<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farmer\FarmerSetting;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request, FirebaseService $firebaseService)
    {
        $request->validate($this->sessionValidationRules());

        $mobile = $request->mobile;
        $farmer = DB::table('farmers')->where('mobile', $mobile)->first();

        if (! $farmer) {
            return response()->json([
                'status' => true,
                'message' => 'New user, complete farmer details',
                'mobile' => $mobile,
                'is_registered' => false,
                'farmer_name' => null,
                'data' => null,
            ], 200);
        }

        if (! (bool) ($farmer->is_active ?? true)) {
            return $this->inactiveFarmerResponse();
        }

        $sessionResult = $this->prepareSessionResponse($request, $farmer, $firebaseService);
        if ($sessionResult['force_logout']) {
            return $this->forceLogoutResponse();
        }

        return response()->json([
            'status' => true,
            'message' => 'Login successful',
            'mobile' => $mobile,
            'is_registered' => true,
            'farmer_name' => $farmer->first_name ?? '',
            'session_token' => $sessionResult['session_token'],
            'data' => $sessionResult['farmer'],
        ], 200);
    }

    public function checkUser(Request $request, FirebaseService $firebaseService)
    {
        $request->validate($this->sessionValidationRules());

        $mobile = $request->mobile;
        $farmer = DB::table('farmers')->where('mobile', $mobile)->first();

        if (! $farmer) {
            return response()->json([
                'status' => true,
                'message' => 'User not registered',
                'mobile' => $mobile,
                'is_registered' => false,
                'farmer_name' => null,
                'data' => null,
            ], 200);
        }

        if (! (bool) ($farmer->is_active ?? true)) {
            return $this->inactiveFarmerResponse();
        }

        $sessionResult = $this->prepareSessionResponse($request, $farmer, $firebaseService);
        if ($sessionResult['force_logout']) {
            return $this->forceLogoutResponse();
        }

        return response()->json([
            'status' => true,
            'message' => 'User already registered',
            'mobile' => $mobile,
            'is_registered' => true,
            'farmer_name' => $farmer->first_name ?? '',
            'session_token' => $sessionResult['session_token'],
            'data' => $sessionResult['farmer'],
        ], 200);
    }

    public function logout(Request $request)
    {
        return response()->json([
            'status' => true,
            'message' => 'Logout successful',
        ], 200);
    }

    private function sessionValidationRules(): array
    {
        return [
            'mobile' => 'required|digits:10',
            'device_id' => 'nullable|string|max:120',
            'session_token' => 'nullable|string|max:120',
            'fcm_token' => 'nullable|string',
            'start_session' => 'nullable|boolean',
        ];
    }

    private function prepareSessionResponse(Request $request, object $farmer, FirebaseService $firebaseService): array
    {
        $deviceId = trim((string) $request->input('device_id', ''));
        $sessionToken = trim((string) $request->input('session_token', ''));
        $fcmToken = trim((string) $request->input('fcm_token', ''));
        $startSession = $request->boolean('start_session');
        $activeToken = trim((string) ($farmer->active_session_token ?? ''));
        $activeDeviceId = trim((string) ($farmer->active_device_id ?? ''));

        if ($startSession) {
            $newSessionToken = Str::random(64);
            $previousFcmToken = trim((string) ($farmer->fcm_token ?? ''));
            $previousDeviceId = $activeDeviceId;

            DB::table('farmers')->where('id', $farmer->id)->update([
                'active_device_id' => $deviceId !== '' ? $deviceId : null,
                'active_session_token' => $newSessionToken,
                'active_session_at' => now(),
                'fcm_token' => $fcmToken !== '' ? $fcmToken : ($farmer->fcm_token ?? null),
                'updated_at' => now(),
            ]);

            if ($previousFcmToken !== '' &&
                ($fcmToken === '' || $previousFcmToken !== $fcmToken) &&
                ($deviceId === '' || $previousDeviceId !== $deviceId)) {
                $this->notifyPreviousDeviceLogout($firebaseService, $previousFcmToken);
            }

            return [
                'force_logout' => false,
                'session_token' => $newSessionToken,
                'farmer' => DB::table('farmers')->where('id', $farmer->id)->first(),
            ];
        }

        if ($activeToken !== '') {
            $sameToken = $sessionToken !== '' && hash_equals($activeToken, $sessionToken);
            $sameDeviceWithoutToken = $sessionToken === '' && $deviceId !== '' && $activeDeviceId === $deviceId;
            if (! $sameToken && ! $sameDeviceWithoutToken) {
                return [
                    'force_logout' => true,
                    'session_token' => '',
                    'farmer' => $farmer,
                ];
            }
        } elseif ($deviceId !== '') {
            $activeToken = Str::random(64);
            DB::table('farmers')->where('id', $farmer->id)->update([
                'active_device_id' => $deviceId,
                'active_session_token' => $activeToken,
                'active_session_at' => now(),
                'fcm_token' => $fcmToken !== '' ? $fcmToken : ($farmer->fcm_token ?? null),
                'updated_at' => now(),
            ]);
        }

        DB::table('farmers')->where('id', $farmer->id)->update([
            'active_session_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'force_logout' => false,
            'session_token' => $activeToken,
            'farmer' => DB::table('farmers')->where('id', $farmer->id)->first(),
        ];
    }

    private function forceLogoutResponse()
    {
        return response()->json([
            'status' => false,
            'message' => 'This account is logged in on another device.',
            'force_logout' => true,
        ], 401);
    }

    private function notifyPreviousDeviceLogout(FirebaseService $firebaseService, string $token): void
    {
        $firebaseService->sendToDevice(
            $token,
            'Logged out',
            'Your account was logged in on another mobile.',
            [
                'type' => 'force_logout',
                'event' => 'force_logout',
                'message' => 'Your account was logged in on another mobile.',
            ]
        );
    }

    private function inactiveFarmerResponse()
    {
        $setting = FarmerSetting::query()->first();
        $adminName = trim((string) ($setting->admin_contact_name ?? 'Corzin Admin'));
        $adminNumber = trim((string) ($setting->admin_contact_number ?? ''));
        $contactText = $adminNumber !== ''
            ? "Please contact admin: {$adminNumber}"
            : 'Please contact admin.';

        return response()->json([
            'status' => false,
            'message' => "Your account is inactive. {$contactText}",
            'account_inactive' => true,
            'admin_contact' => [
                'name' => $adminName,
                'number' => $adminNumber,
            ],
        ], 403);
    }
}




