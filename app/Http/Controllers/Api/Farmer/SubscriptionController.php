<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farmer\Farmer;
use App\Models\Farmer\FarmerPlan;
use App\Models\Farmer\FarmerSubscription;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function plans(Request $request)
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

        $currentSubscription = $this->currentSubscriptionForFarmer((int) $request->query('farmer_id', 0), $plans);
        $currentPlanId = (int) optional($currentSubscription)->farmer_plan_id;
        $maxPrice = (float) $plans->max('price');
        $data = $plans->map(function (FarmerPlan $plan) use ($maxPrice, $currentPlanId) {
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
                'is_current' => $currentPlanId > 0 && $plan->id === $currentPlanId,
            ];
        })->values();

        return response()->json([
            'status' => true,
            'message' => 'Subscription plans fetched successfully.',
            'data' => $data,
            'current_subscription' => $this->transformSubscription($currentSubscription),
            'access_locked' => $currentSubscription
                ? ! $this->isSubscriptionUsable($currentSubscription)
                : false,
        ]);
    }

    private function currentSubscriptionForFarmer(int $farmerId, $plans): ?FarmerSubscription
    {
        if ($farmerId <= 0) {
            return null;
        }

        $farmer = Farmer::query()->find($farmerId);
        if (! $farmer) {
            return null;
        }

        $subscription = FarmerSubscription::query()
            ->with('plan')
            ->where('farmer_id', $farmerId)
            ->latest('id')
            ->first();

        if (! $subscription) {
            $freePlan = $plans->first(fn (FarmerPlan $plan) => (float) $plan->price <= 0);
            if ($freePlan) {
                $startDate = $farmer->created_at
                    ? Carbon::parse($farmer->created_at)->startOfDay()
                    : now()->startOfDay();
                $dueDate = (clone $startDate)->addDays(max((int) $freePlan->duration_days, 30));
                $subscription = FarmerSubscription::create([
                    'farmer_id' => $farmerId,
                    'farmer_plan_id' => $freePlan->id,
                    'start_date' => $startDate->toDateString(),
                    'due_date' => $dueDate->toDateString(),
                    'status' => $dueDate->isPast() ? 'expired' : 'active',
                    'notes' => 'Auto-created free trial subscription.',
                ])->load('plan');
            }
        }

        if ($subscription && $subscription->due_date && $subscription->due_date->isPast() && $subscription->status === 'active') {
            $subscription->update(['status' => 'expired']);
            $subscription->refresh()->load('plan');
        }

        return $subscription;
    }

    private function isSubscriptionUsable(FarmerSubscription $subscription): bool
    {
        if (strtolower((string) $subscription->status) !== 'active') {
            return false;
        }

        if ($subscription->due_date && $subscription->due_date->isPast()) {
            return false;
        }

        return true;
    }

    private function transformSubscription(?FarmerSubscription $subscription): ?array
    {
        if (! $subscription) {
            return null;
        }

        $plan = $subscription->plan;
        $today = now()->startOfDay();
        $dueDate = $subscription->due_date ? $subscription->due_date->copy()->startOfDay() : null;
        $daysLeft = $dueDate ? $today->diffInDays($dueDate, false) : null;

        return [
            'id' => $subscription->id,
            'farmer_id' => $subscription->farmer_id,
            'farmer_plan_id' => $subscription->farmer_plan_id,
            'plan_name' => $plan->name ?? 'Plan',
            'price' => $plan ? (float) $plan->price : 0,
            'price_label' => 'Rs '.number_format($plan ? (float) $plan->price : 0, 2),
            'duration_days' => $plan ? (int) $plan->duration_days : 0,
            'start_date' => optional($subscription->start_date)->toDateString(),
            'due_date' => optional($subscription->due_date)->toDateString(),
            'status' => $subscription->status,
            'is_active' => $this->isSubscriptionUsable($subscription),
            'days_left' => $daysLeft,
            'expiry_text' => $this->expiryTextFromDays($daysLeft),
        ];
    }

    private function expiryTextFromDays(?int $daysLeft): string
    {
        if ($daysLeft === null) {
            return '-';
        }

        if ($daysLeft > 0) {
            return 'Expires in '.$daysLeft.' day'.($daysLeft === 1 ? '' : 's');
        }

        if ($daysLeft === 0) {
            return 'Expires today';
        }

        $expiredDays = abs($daysLeft);

        return 'Expired '.$expiredDays.' day'.($expiredDays === 1 ? '' : 's').' ago';
    }
}
