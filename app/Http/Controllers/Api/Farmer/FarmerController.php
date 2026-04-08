<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        ]);

        $farmer = DB::table('farmers')
            ->where('mobile', $request->mobile)
            ->first();

        /// 🔄 UPDATE EXISTING FARMER
        if ($farmer) {
            DB::table('farmers')
                ->where('mobile', $request->mobile)
                ->update([
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
                ]);

            $updatedFarmer = DB::table('farmers')
                ->where('mobile', $request->mobile)
                ->first();

            return response()->json([
                'status' => true,
                'message' => 'Farmer updated successfully',
                'is_registered' => true,
                'farmer_name' => $updatedFarmer->first_name ?? '',
                'data' => $updatedFarmer,
            ], 200);
        }

        /// 🆕 CREATE NEW FARMER
        DB::table('farmers')->insert([
            'mobile' => $request->mobile,
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'village' => $request->village,
            'city' => $request->city,
            'taluka' => $request->taluka,
            'district' => $request->district,
            'state' => $request->state,
            'pincode' => $request->pincode,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $newFarmer = DB::table('farmers')
            ->where('mobile', $request->mobile)
            ->first();

        return response()->json([
            'status' => true,
            'message' => 'Farmer created successfully',
            'is_registered' => true,
            'farmer_name' => $newFarmer->first_name ?? '',
            'data' => $newFarmer,
        ], 201);
    }

    public function getProfileByMobile($mobile)
    {
        $farmer = DB::table('farmers')
            ->where('mobile', $mobile)
            ->first();

        if (!$farmer) {
            return response()->json([
                'status' => false,
                'message' => 'Farmer not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Farmer profile fetched successfully',
            'data' => $farmer,
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $farmer = DB::table('farmers')->where('id', $id)->first();

        if (!$farmer) {
            return response()->json([
                'status' => false,
                'message' => 'Farmer not found'
            ], 404);
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
            'updated_at' => now(),
        ]);

        $updatedFarmer = DB::table('farmers')->where('id', $id)->first();

        return response()->json([
            'status' => true,
            'message' => 'Farmer updated successfully',
            'data' => $updatedFarmer,
        ], 200);
    }
}