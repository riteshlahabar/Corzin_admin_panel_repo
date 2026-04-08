<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Doctor\DoctorBanner;
use App\Models\Doctor\DoctorSetting;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DoctorSettingController extends Controller
{
    public function index()
    {
        $setting = DoctorSetting::query()->first();

        if (! $setting) {
            $setting = DoctorSetting::create([
                'terms_and_conditions' => '',
                'privacy_policy' => '',
            ]);
        }

        $banners = DoctorBanner::query()
            ->orderBy('sort_order')
            ->latest('id')
            ->get();

        return view('doctor.settings', compact('setting', 'banners'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'terms_and_conditions' => ['nullable', 'string'],
            'privacy_policy' => ['nullable', 'string'],
        ]);

        $setting = DoctorSetting::query()->first();
        if (! $setting) {
            $setting = new DoctorSetting();
        }

        if ($request->has('terms_and_conditions')) {
            $setting->terms_and_conditions = $data['terms_and_conditions'] ?? '';
        }

        if ($request->has('privacy_policy')) {
            $setting->privacy_policy = $data['privacy_policy'] ?? '';
        }

        $setting->save();

        return redirect()
            ->route('doctor.settings')
            ->with('success', 'Doctor settings updated successfully.');
    }

    public function uploadBanner(Request $request)
    {
        $payload = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'banner_image' => ['required', 'image', 'max:5120'],
        ]);

        $directory = public_path('assets/doctor_banner_images');
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $file = $request->file('banner_image');
        $filename = 'doctor_banner_'.time().'_'.Str::random(8).'.'.$file->getClientOriginalExtension();
        $file->move($directory, $filename);

        $nextSortOrder = (int) DoctorBanner::max('sort_order') + 1;

        DoctorBanner::create([
            'title' => $payload['title'] ?? null,
            'image_path' => 'assets/doctor_banner_images/'.$filename,
            'is_active' => true,
            'sort_order' => $nextSortOrder,
        ]);

        return redirect()
            ->route('doctor.settings')
            ->with('success', 'Banner uploaded successfully.');
    }

    public function destroyBanner(DoctorBanner $doctorBanner)
    {
        if (! blank($doctorBanner->image_path)) {
            $absolutePath = public_path($doctorBanner->image_path);
            if (File::exists($absolutePath)) {
                File::delete($absolutePath);
            }
        }

        $doctorBanner->delete();

        return redirect()
            ->route('doctor.settings')
            ->with('success', 'Banner removed successfully.');
    }
}
