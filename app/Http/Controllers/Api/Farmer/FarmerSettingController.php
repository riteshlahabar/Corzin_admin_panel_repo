<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farmer\FarmerBanner;

class FarmerSettingController extends Controller
{
    public function show()
    {
        $banners = FarmerBanner::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->latest('id')
            ->get()
            ->map(function (FarmerBanner $banner) {
                return [
                    'id' => $banner->id,
                    'title' => $banner->title ?? '',
                    'image' => $banner->image_path ?? '',
                    'image_url' => blank($banner->image_path) ? '' : asset($banner->image_path),
                    'sort_order' => (int) $banner->sort_order,
                ];
            })
            ->values();

        return response()->json([
            'status' => true,
            'message' => 'Farmer settings fetched successfully.',
            'data' => [
                'banners' => $banners,
            ],
        ]);
    }
}
