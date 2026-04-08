<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

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
                'data' => $this->transformFarmer($updatedFarmer),
            ], 200);
        }

        /// 🆕 CREATE NEW FARMER
        $farmerId = DB::table('farmers')->insertGetId([
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
            'farmer_photo' => $farmer->farmer_photo ?? '',
            'farmer_photo_url' => ! empty($farmer->farmer_photo) ? asset($farmer->farmer_photo) : '',
            'created_at' => $farmer->created_at ?? null,
            'updated_at' => $farmer->updated_at ?? null,
        ];
    }
}
