<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'mobile' => 'required|digits:10',
        ]);

        $mobile = $request->mobile;

        $farmer = DB::table('farmers')
            ->where('mobile', $mobile)
            ->first();

        /// 🆕 NEW USER
        if (!$farmer) {
            return response()->json([
                'status' => true,
                'message' => 'New user, complete farmer details',
                'mobile' => $mobile,
                'is_registered' => false,
                'farmer_name' => null,
                'data' => null,
            ], 200);
        }

        /// ✅ EXISTING USER
        return response()->json([
            'status' => true,
            'message' => 'Login successful',
            'mobile' => $mobile,
            'is_registered' => true,
            'farmer_name' => $farmer->first_name ?? '',
            'data' => $farmer,
        ], 200);
    }

    public function checkUser(Request $request)
    {
        $request->validate([
            'mobile' => 'required|digits:10',
        ]);

        $mobile = $request->mobile;

        $farmer = DB::table('farmers')
            ->where('mobile', $mobile)
            ->first();

        if (!$farmer) {
            return response()->json([
                'status' => true,
                'message' => 'User not registered',
                'mobile' => $mobile,
                'is_registered' => false,
                'farmer_name' => null,
                'data' => null,
            ], 200);
        }

        return response()->json([
            'status' => true,
            'message' => 'User already registered',
            'mobile' => $mobile,
            'is_registered' => true,
            'farmer_name' => $farmer->first_name ?? '',
            'data' => $farmer,
        ], 200);
    }

    public function logout(Request $request)
    {
        return response()->json([
            'status' => true,
            'message' => 'Logout successful'
        ], 200);
    }
}