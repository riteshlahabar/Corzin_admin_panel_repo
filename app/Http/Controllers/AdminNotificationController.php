<?php

namespace App\Http\Controllers;

use App\Models\Doctor\DoctorAdminNotification;
use App\Models\Shop\ShopAdminNotification;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    public function markRead(Request $request, string $source, int $id)
    {
        $source = strtolower(trim($source));

        if ($source === 'doctor') {
            DoctorAdminNotification::query()->whereKey($id)->update(['is_read' => true]);
        } elseif ($source === 'shop') {
            ShopAdminNotification::query()->whereKey($id)->update(['is_read' => true]);
        }

        return back();
    }
}

