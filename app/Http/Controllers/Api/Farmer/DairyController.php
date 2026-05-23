<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Dairy\Dairy;
use App\Models\Dairy\DairyPaymentEntry;
use App\Models\Farmer\MilkProduction;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Collection;

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
        $today = now()->toDateString();
        $milkRowsByDairy = MilkProduction::query()
            ->with(['animal:id,animal_name,tag_number'])
            ->whereIn('dairy_id', $dairyIds)
            ->orderBy('date')
            ->get()
            ->groupBy('dairy_id');

        $milkQuantityByDairy = MilkProduction::query()
            ->selectRaw(
                'dairy_id, COALESCE(SUM(total_milk), 0) as total_milk, COALESCE(SUM(CASE WHEN DATE(`date`) = ? THEN total_milk ELSE 0 END), 0) as today_milk',
                [$today]
            )
            ->whereIn('dairy_id', $dairyIds)
            ->groupBy('dairy_id')
            ->get()
            ->keyBy('dairy_id');

        $paymentEntriesByDairy = DairyPaymentEntry::query()
            ->where('farmer_id', $farmer_id)
            ->whereIn('dairy_id', $dairyIds)
            ->orderBy('payment_date')
            ->get()
            ->groupBy('dairy_id');

        $data = $dairies->map(function (Dairy $dairy) use ($milkRowsByDairy, $paymentEntriesByDairy, $milkQuantityByDairy) {
            $ledger = $this->buildLedgerForDairy(
                $dairy,
                $milkRowsByDairy->get($dairy->id, collect()),
                $paymentEntriesByDairy->get($dairy->id, collect())
            );
            $latest = $ledger['history']->first();
            $milkQuantity = $milkQuantityByDairy->get($dairy->id);

            return [
                'id' => $dairy->id,
                'dairy_name' => $dairy->dairy_name,
                'today_milk' => round((float) ($milkQuantity->today_milk ?? 0), 2),
                'total_milk' => round((float) ($milkQuantity->total_milk ?? 0), 2),
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
        $payableContext = $this->calculatePayableForDate($farmerId, $dairyId, $paymentDate);
        $previousBalance = round((float) ($payableContext['previous_balance'] ?? 0), 2);
        $dayTotalAmount = round((float) ($payableContext['day_total_amount'] ?? 0), 2);
        $totalAmount = round((float) ($payableContext['total_amount'] ?? 0), 2);
        $paidBefore = round((float) ($payableContext['paid_amount'] ?? 0), 2);
        $remainingBefore = round((float) ($payableContext['remaining_balance'] ?? 0), 2);
        $paidAmount = round((float) $request->input('paid_amount', 0), 2);
        if ($paidAmount > $remainingBefore) {
            return response()->json([
                'status' => false,
                'message' => [
                    'paid_amount' => ['Paid amount cannot be greater than total balance amount.'],
                ],
            ], 422);
        }
        $closingBalance = round($remainingBefore - $paidAmount, 2);

        $entry = DairyPaymentEntry::query()->create([
            'farmer_id' => $farmerId,
            'dairy_id' => $dairyId,
            'payment_date' => $paymentDate,
            'opening_balance' => $remainingBefore,
            'day_total_amount' => $dayTotalAmount,
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'closing_balance' => $closingBalance,
            'notes' => trim((string) $request->input('notes', '')),
        ]);

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
                'previous_balance' => $previousBalance,
                'today_balance' => $dayTotalAmount,
                'day_total_amount' => $dayTotalAmount,
                'paid_amount_before' => $paidBefore,
                'paid_date' => optional($entry->payment_date)->format('d/m/Y') ?? '',
                'opening_balance' => round((float) $entry->opening_balance, 2),
                'closing_balance' => round((float) $entry->closing_balance, 2),
                'total_balance' => $closingBalance,
                'balance_amount' => $closingBalance,
                'notes' => (string) ($entry->notes ?? ''),
            ],
        ]);
    }

    private function buildLedgerForDairy(Dairy $dairy, Collection $milkRows, Collection $entries)
    {
        $milkByDate = $milkRows
            ->groupBy(function (MilkProduction $milk) {
                $rawDate = trim((string) ($milk->date ?? ''));
                if ($rawDate === '') {
                    return '';
                }
                return Carbon::parse($rawDate)->toDateString();
            })
            ->mapWithKeys(function (Collection $rows, $date) {
                if (! is_string($date) || trim($date) === '') {
                    return [];
                }

                $animalRows = $rows
                    ->groupBy('animal_id')
                    ->map(function (Collection $animalMilkRows) {
                        /** @var MilkProduction $firstRow */
                        $firstRow = $animalMilkRows->first();
                        $animalName = (string) optional($firstRow->animal)->animal_name;
                        $tagNumber = (string) optional($firstRow->animal)->tag_number;
                        $morning = round((float) $animalMilkRows->sum('morning_milk'), 2);
                        $afternoon = round((float) $animalMilkRows->sum('afternoon_milk'), 2);
                        $evening = round((float) $animalMilkRows->sum('evening_milk'), 2);
                        $totalMilk = round((float) $animalMilkRows->sum('total_milk'), 2);

                        return [
                            'animal_name' => trim($animalName) !== '' ? $animalName : '-',
                            'tag_number' => trim($tagNumber),
                            'morning_milk' => $morning,
                            'afternoon_milk' => $afternoon,
                            'evening_milk' => $evening,
                            'total_milk' => $totalMilk,
                        ];
                    })
                    ->values();

                $dateTotalMilk = round((float) $animalRows->sum('total_milk'), 2);
                $dayTotalAmount = round(
                    (float) $rows->sum(function (MilkProduction $row) {
                        return ((float) ($row->total_milk ?? 0)) * ((float) ($row->rate ?? 0));
                    }),
                    2
                );
                $effectiveRate = $dateTotalMilk > 0
                    ? round($dayTotalAmount / $dateTotalMilk, 2)
                    : round((float) ($rows->first()->rate ?? 0), 2);

                return [
                    $date => [
                        'animals' => $animalRows,
                        'total_milk' => $dateTotalMilk,
                        'day_total_amount' => $dayTotalAmount,
                        'rate' => $effectiveRate,
                    ],
                ];
            });

        $manualByDate = collect($entries)
            ->groupBy(function (DairyPaymentEntry $entry) {
                return optional($entry->payment_date)->toDateString();
            })
            ->map(function (Collection $rows, $date) {
                $paidAmount = round((float) $rows->sum('paid_amount'), 2);
                $note = (string) ($rows->sortByDesc('id')->first()->notes ?? '');
                return [
                    'paid_amount' => $paidAmount,
                    'notes' => $note,
                    'paid_date' => $paidAmount > 0 ? Carbon::parse((string) $date)->format('d/m/Y') : '',
                ];
            })
            ->mapWithKeys(function ($entry, $date) {
                if (! is_string($date) || trim($date) === '') {
                    return [];
                }
                return [$date => $entry];
            });

        $dateSet = collect()
            ->merge($milkByDate->keys())
            ->merge($manualByDate->keys())
            ->filter(fn ($date) => is_string($date) && trim($date) !== '')
            ->unique()
            ->sort()
            ->values();

        $runningBalance = 0.0;
        $rows = [];

        foreach ($dateSet as $date) {
            $milkEntry = $milkByDate->get($date, [
                'animals' => collect(),
                'total_milk' => 0,
                'day_total_amount' => 0,
                'rate' => 0,
            ]);
            $manualEntry = $manualByDate->get($date, [
                'paid_amount' => 0,
                'notes' => '',
                'paid_date' => '',
            ]);

            $previousBalance = round($runningBalance, 2);
            $todayBalance = round((float) ($milkEntry['day_total_amount'] ?? 0), 2);
            $totalAmount = round($previousBalance + $todayBalance, 2);
            $paidAmount = round((float) ($manualEntry['paid_amount'] ?? 0), 2);
            $totalBalance = round($totalAmount - $paidAmount, 2);
            $runningBalance = $totalBalance;

            $rows[] = [
                'date' => Carbon::parse($date)->format('d/m/Y'),
                'date_key' => $date,
                'dairy_id' => $dairy->id,
                'dairy_name' => $dairy->dairy_name,
                'animals' => $milkEntry['animals'],
                'total_milk' => round((float) ($milkEntry['total_milk'] ?? 0), 2),
                'rate' => round((float) ($milkEntry['rate'] ?? 0), 2),
                'previous_balance' => $previousBalance,
                'today_balance' => $todayBalance,
                'day_total_amount' => $todayBalance,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'paid_date' => (string) ($manualEntry['paid_date'] ?? ''),
                'total_balance' => $totalBalance,
                'balance_amount' => $totalBalance,
                'notes' => (string) ($manualEntry['notes'] ?? ''),
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

    private function calculatePayableForDate(int $farmerId, int $dairyId, string $paymentDate): array
    {
        $milkByDay = MilkProduction::query()
            ->selectRaw('DATE(`date`) as entry_date, COALESCE(SUM(total_milk * COALESCE(rate, 0)), 0) as day_total_amount')
            ->where('dairy_id', $dairyId)
            ->groupBy('entry_date')
            ->get()
            ->mapWithKeys(function ($row) {
                return [
                    (string) $row->entry_date => round((float) ($row->day_total_amount ?? 0), 2),
                ];
            });

        $paidByDate = DairyPaymentEntry::query()
            ->selectRaw('DATE(payment_date) as entry_date, COALESCE(SUM(paid_amount), 0) as paid_total')
            ->where('farmer_id', $farmerId)
            ->where('dairy_id', $dairyId)
            ->groupBy('entry_date')
            ->get()
            ->mapWithKeys(function ($row) {
                return [
                    (string) $row->entry_date => round((float) ($row->paid_total ?? 0), 2),
                ];
            });

        $dateSet = collect()
            ->merge($milkByDay->keys())
            ->merge($paidByDate->keys())
            ->push($paymentDate)
            ->filter(fn ($date) => is_string($date) && trim($date) !== '')
            ->unique()
            ->sort()
            ->values();

        $runningBalance = 0.0;
        foreach ($dateSet as $date) {
            $dayTotalAmount = (float) ($milkByDay->get($date) ?? 0);
            $totalAmount = $runningBalance + $dayTotalAmount;
            $paidAmount = round((float) ($paidByDate->get($date) ?? 0), 2);
            $balance = $totalAmount - $paidAmount;

            if ($date === $paymentDate) {
                return [
                    'previous_balance' => round($runningBalance, 2),
                    'day_total_amount' => round($dayTotalAmount, 2),
                    'total_amount' => round($totalAmount, 2),
                    'paid_amount' => round($paidAmount, 2),
                    'remaining_balance' => round($balance, 2),
                ];
            }

            $runningBalance = $balance;
        }

        return [
            'previous_balance' => 0.0,
            'day_total_amount' => 0.0,
            'total_amount' => 0.0,
            'paid_amount' => 0.0,
            'remaining_balance' => 0.0,
        ];
    }
}
