<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Dairy\Dairy;
use App\Models\Dairy\DairyPaymentEntry;
use App\Models\Farmer\MilkProduction;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DairyController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'farmer_id' => 'required|exists:farmers,id',
            'dairy_name' => 'required|string|max:255',
            'gst_no' => 'nullable|string|max:50',
            'contact_number' => 'nullable|string|max:30',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'taluka' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'pincode' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $dairy = Dairy::create([
            'farmer_id' => $request->farmer_id,
            'dairy_name' => $request->dairy_name,
            'gst_no' => $request->gst_no,
            'contact_number' => $request->contact_number,
            'address' => $request->address,
            'city' => $request->city,
            'taluka' => $request->taluka,
            'district' => $request->district,
            'state' => $request->state,
            'pincode' => $request->pincode,
            'village' => $request->city,
            'is_active' => true,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Dairy added successfully',
            'data' => $this->transformDairy($dairy->load('farmer')),
        ], 201);
    }

    public function index($farmer_id)
    {
        $dairies = Dairy::with('farmer')
            ->where('farmer_id', $farmer_id)
            ->latest()
            ->get()
            ->map(fn (Dairy $dairy) => $this->transformDairy($dairy));

        return response()->json([
            'status' => true,
            'message' => 'Dairies fetched successfully',
            'data' => $dairies,
        ]);
    }

    public function payments($farmer_id)
    {
        $dairies = Dairy::where('farmer_id', $farmer_id)->latest()->get();
        $dairyIds = $dairies->pluck('id')->values();

        $milkByDay = MilkProduction::query()
            ->selectRaw('dairy_id, DATE(`date`) as entry_date, COALESCE(SUM(total_milk * COALESCE(rate, 0)), 0) as day_total_amount')
            ->whereIn('dairy_id', $dairyIds)
            ->groupBy('dairy_id', 'entry_date')
            ->get()
            ->groupBy('dairy_id')
            ->map(function ($rows) {
                return collect($rows)->mapWithKeys(function ($row) {
                    return [
                        (string) $row->entry_date => round((float) ($row->day_total_amount ?? 0), 2),
                    ];
                });
            });

        $paymentEntriesByDairy = DairyPaymentEntry::query()
            ->where('farmer_id', $farmer_id)
            ->whereIn('dairy_id', $dairyIds)
            ->orderBy('payment_date')
            ->get()
            ->groupBy('dairy_id');

        $data = $dairies->map(function (Dairy $dairy) use ($milkByDay, $paymentEntriesByDairy) {
            $ledger = $this->buildLedgerForDairy(
                $dairy,
                $milkByDay->get($dairy->id, collect()),
                $paymentEntriesByDairy->get($dairy->id, collect())
            );
            $latest = $ledger['history']->first();

            return [
                'id' => $dairy->id,
                'dairy_name' => $dairy->dairy_name,
                'current_balance' => round((float) ($ledger['current_balance'] ?? 0), 2),
                'history' => $ledger['history'],
                'latest' => $latest,
            ];
        })->values();

        return response()->json([
            'status' => true,
            'message' => 'Dairy payments fetched successfully',
            'data' => $data,
        ]);
    }

    public function storePaymentEntry(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'farmer_id' => 'required|exists:farmers,id',
            'dairy_id' => 'required|exists:dairies,id',
            'payment_date' => 'nullable|date',
            'total_amount' => 'nullable|numeric|min:0',
            'paid_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $farmerId = (int) $request->input('farmer_id');
        $dairyId = (int) $request->input('dairy_id');

        $dairy = Dairy::query()
            ->where('id', $dairyId)
            ->where('farmer_id', $farmerId)
            ->first();

        if (! $dairy) {
            return response()->json([
                'status' => false,
                'message' => 'Selected dairy does not belong to this farmer.',
            ], 422);
        }

        $paymentDate = Carbon::parse($request->input('payment_date', now()->toDateString()))->toDateString();

        $entry = DairyPaymentEntry::query()->updateOrCreate(
            [
                'farmer_id' => $farmerId,
                'dairy_id' => $dairyId,
                'payment_date' => $paymentDate,
            ],
            [
                'total_amount' => round((float) $request->input('total_amount', 0), 2),
                'paid_amount' => round((float) $request->input('paid_amount', 0), 2),
                'notes' => trim((string) $request->input('notes', '')),
            ]
        );

        return response()->json([
            'status' => true,
            'message' => 'Payment entry saved successfully.',
            'data' => [
                'id' => $entry->id,
                'dairy_id' => $entry->dairy_id,
                'farmer_id' => $entry->farmer_id,
                'payment_date' => optional($entry->payment_date)->toDateString() ?? $paymentDate,
                'total_amount' => round((float) $entry->total_amount, 2),
                'paid_amount' => round((float) $entry->paid_amount, 2),
                'notes' => (string) ($entry->notes ?? ''),
            ],
        ]);
    }

    private function buildLedgerForDairy(Dairy $dairy, $milkByDay, $entries)
    {
        $manualByDate = collect($entries)->mapWithKeys(function (DairyPaymentEntry $entry) {
            $date = optional($entry->payment_date)->toDateString();
            return $date ? [$date => $entry] : [];
        });

        $dateSet = collect()
            ->merge($milkByDay->keys())
            ->merge($manualByDate->keys())
            ->filter(fn ($date) => is_string($date) && trim($date) !== '')
            ->unique()
            ->sort()
            ->values();

        $runningBalance = 0.0;
        $rows = [];

        foreach ($dateSet as $date) {
            /** @var DairyPaymentEntry|null $manualEntry */
            $manualEntry = $manualByDate->get($date);
            $milkDayTotal = (float) ($milkByDay->get($date) ?? 0);
            // Daily amount is always derived from milk production.
            // Payment entries only affect paid amount / notes for that date.
            $dayTotalAmount = $milkDayTotal;
            $paidAmount = (float) ($manualEntry->paid_amount ?? 0);
            $previousBalance = $runningBalance;
            $totalAmount = $previousBalance + $dayTotalAmount;
            $balance = $totalAmount - $paidAmount;
            $runningBalance = $balance;

            $rows[] = [
                'date' => Carbon::parse($date)->format('d/m/Y'),
                'date_key' => $date,
                'dairy_id' => $dairy->id,
                'dairy_name' => $dairy->dairy_name,
                'previous_balance' => round($previousBalance, 2),
                'day_total_amount' => round($dayTotalAmount, 2),
                'total_amount' => round($totalAmount, 2),
                'paid_amount' => round($paidAmount, 2),
                'balance_amount' => round($balance, 2),
                'notes' => (string) ($manualEntry->notes ?? ''),
            ];
        }

        return [
            'current_balance' => round($runningBalance, 2),
            'history' => collect($rows)->sortByDesc('date_key')->values(),
        ];
    }

    private function transformDairy(Dairy $dairy): array
    {
        return [
            'id' => $dairy->id,
            'farmer_id' => $dairy->farmer_id,
            'farmer_name' => trim(($dairy->farmer->first_name ?? '').' '.($dairy->farmer->last_name ?? '')),
            'dairy_name' => $dairy->dairy_name,
            'gst_no' => $dairy->gst_no,
            'contact_number' => $dairy->contact_number,
            'address' => $dairy->address,
            'city' => $dairy->city,
            'taluka' => $dairy->taluka,
            'district' => $dairy->district,
            'state' => $dairy->state,
            'pincode' => $dairy->pincode,
            'is_active' => $dairy->is_active,
        ];
    }
}
