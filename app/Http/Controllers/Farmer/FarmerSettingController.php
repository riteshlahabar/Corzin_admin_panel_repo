<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farmer\FarmerBanner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class FarmerSettingController extends Controller
{
    public function index()
    {
        $banners = FarmerBanner::query()
            ->orderBy('sort_order')
            ->latest('id')
            ->get();

        return view('farmer.settings', compact('banners'));
    }

    public function uploadBanner(Request $request)
    {
        $payload = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'banner_image' => ['required', 'image', 'max:5120'],
        ]);

        $directory = public_path('assets/farmer_banner_images');
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $file = $request->file('banner_image');
        $filename = 'farmer_banner_'.time().'_'.Str::random(8).'.'.$file->getClientOriginalExtension();
        $file->move($directory, $filename);

        FarmerBanner::create([
            'title' => $payload['title'] ?? null,
            'image_path' => 'assets/farmer_banner_images/'.$filename,
            'is_active' => true,
            'sort_order' => ((int) FarmerBanner::max('sort_order')) + 1,
        ]);

        return redirect()
            ->route('farmer.settings')
            ->with('success', 'Banner uploaded successfully.');
    }

    public function destroyBanner(FarmerBanner $farmerBanner)
    {
        if (! blank($farmerBanner->image_path)) {
            $absolutePath = public_path($farmerBanner->image_path);
            if (File::exists($absolutePath)) {
                File::delete($absolutePath);
            }
        }

        $farmerBanner->delete();

        return redirect()
            ->route('farmer.settings')
            ->with('success', 'Banner removed successfully.');
    }
}
