<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\FirebaseService;

class FirebaseAuth
{
    protected $firebase;

    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    public function handle(Request $request, Closure $next)
    {
        try {

            // 🔥 STEP 1: Get bypass numbers from config
            $bypassNumbers = config('app.bypass_numbers', []);

            // 🔥 STEP 2: Get mobile from request
            $mobile = $request->input('mobile');

            // ✅ STEP 3: Bypass condition
            if ($mobile && in_array($mobile, $bypassNumbers)) {
                return $next($request);
            }

            // 🔐 STEP 4: Firebase Token Check
            $authHeader = $request->header('Authorization');

            if (!$authHeader) {
                return response()->json([
                    'status' => false,
                    'message' => 'Token missing'
                ], 401);
            }

            $idToken = str_replace('Bearer ', '', $authHeader);

            // 🔥 STEP 5: Verify using your FirebaseService
            $verifiedToken = $this->firebase->verifyToken($idToken);

            // 🔥 STEP 6: Extract UID
            $uid = $verifiedToken->claims()->get('sub');

            // Attach UID to request
            $request->merge(['firebase_uid' => $uid]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
                'error' => $e->getMessage()
            ], 401);
        }

        return $next($request);
    }
}