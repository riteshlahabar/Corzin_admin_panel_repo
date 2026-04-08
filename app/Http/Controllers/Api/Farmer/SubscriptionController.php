<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farmer\FarmerPlan;

class SubscriptionController extends Controller
{
    public function plans()
    {
        $plans = FarmerPlan::query()
            ->where('is_active', true)
            ->orderBy('price')
            ->orderBy('id')
            ->get();

        if ($plans->isEmpty()) {
            return response()->json([
                'status' => true,
                'message' => 'No active subscription plan found.',
                'data' => [],
            ]);
        }

        $maxPrice = (float) $plans->max('price');
        $data = $plans->map(function (FarmerPlan $plan) use ($maxPrice) {
            $features = collect(preg_split('/[\r\n,]+/', (string) $plan->features))
                ->map(fn ($item) => trim((string) $item))
                ->filter()
                ->values()
                ->all();

            return [
                'id' => $plan->id,
                'name' => $plan->name,
                'price' => (float) $plan->price,
                'price_label' => 'Rs '.number_format((float) $plan->price, 2),
                'duration_days' => (int) $plan->duration_days,
                'features' => $features,
                'is_popular' => (float) $plan->price === $maxPrice,
            ];
        })->values();

        return response()->json([
            'status' => true,
            'message' => 'Subscription plans fetched successfully.',
            'data' => $data,
        ]);
    }
}
