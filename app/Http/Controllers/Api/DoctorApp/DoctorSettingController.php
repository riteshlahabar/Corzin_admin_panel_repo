<?php

namespace App\Http\Controllers\Api\DoctorApp;

use App\Http\Controllers\Controller;
use App\Models\Doctor\DoctorBanner;
use App\Models\Doctor\DoctorSetting;

class DoctorSettingController extends Controller
{
    public function show()
    {
        $setting = DoctorSetting::query()->first();

        if (! $setting) {
            $setting = DoctorSetting::create([
                'terms_and_conditions' => '',
                'privacy_policy' => '',
            ]);
        }

        $banners = DoctorBanner::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->latest('id')
            ->get()
            ->map(function (DoctorBanner $banner) {
                return [
                    'id' => $banner->id,
                    'title' => $banner->title ?? '',
                    'image' => $banner->image_path ?? '',
                    'image_url' => blank($banner->image_path) ? '' : asset($banner->image_path),
                    'sort_order' => (int) $banner->sort_order,
                ];
            })->values();

        return response()->json([
            'status' => true,
            'message' => 'Doctor settings fetched successfully.',
            'data' => [
                'terms_and_condition' => $setting->terms_and_conditions ?? '',
                'terms_and_conditions' => $setting->terms_and_conditions ?? '',
                'privacy_policy' => $setting->privacy_policy ?? '',
                'banners' => $banners,
            ],
        ]);
    }
}
