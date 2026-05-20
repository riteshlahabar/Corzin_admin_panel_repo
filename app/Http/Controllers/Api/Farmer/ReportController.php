<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Doctor\DoctorAppointment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function livestock(Request $request, $farmer_id)
    {
        $farmerId = (int) $farmer_id;
        [$from, $to] = $this->resolveDateRange($request);

        $scope = strtolower(trim((string) $request->query('scope', 'animal')));
        if (! in_array($scope, ['animal', 'pan'], true)) {
            $scope = 'animal';
        }
        $targetId = (int) $request->query('target_id', 0);

        $rowsMap = [];
        if ($scope === 'pan') {
            $milkRows = DB::table('milk_productions as m')
                ->join('animals as a', 'a.id', '=', 'm.animal_id')
                ->join('farmer_pans as p', 'p.id', '=', 'a.pan_id')
                ->where('a.farmer_id', $farmerId)
                ->whereBetween(DB::raw('DATE(m.date)'), [$from->toDateString(), $to->toDateString()])
                ->when($targetId > 0, fn ($query) => $query->where('p.id', $targetId))
                ->groupBy(DB::raw('DATE(m.date)'), 'p.id', 'p.name')
                ->selectRaw('DATE(m.date) as entry_date, p.id as target_id, p.name as target_name, COALESCE(SUM(m.total_milk),0) as milk_quantity, COALESCE(SUM(m.total_milk * COALESCE(m.rate,0)),0) as milk_amount')
                ->get();

            $feedingRows = DB::table('feeding_records as f')
                ->join('animals as a', 'a.id', '=', 'f.animal_id')
                ->join('farmer_pans as p', 'p.id', '=', 'a.pan_id')
                ->where('a.farmer_id', $farmerId)
                ->whereBetween(DB::raw('DATE(f.date)'), [$from->toDateString(), $to->toDateString()])
                ->when($targetId > 0, fn ($query) => $query->where('p.id', $targetId))
                ->groupBy(DB::raw('DATE(f.date)'), 'p.id', 'p.name')
                ->selectRaw('DATE(f.date) as entry_date, p.id as target_id, p.name as target_name, COALESCE(SUM(COALESCE(f.feeding_quantity, f.quantity, 0)),0) as feeding_quantity')
                ->get();
        } else {
            $milkRows = DB::table('milk_productions as m')
                ->join('animals as a', 'a.id', '=', 'm.animal_id')
                ->where('a.farmer_id', $farmerId)
                ->whereBetween(DB::raw('DATE(m.date)'), [$from->toDateString(), $to->toDateString()])
                ->when($targetId > 0, fn ($query) => $query->where('a.id', $targetId))
                ->groupBy(DB::raw('DATE(m.date)'), 'a.id', 'a.animal_name', 'a.tag_number')
                ->selectRaw("DATE(m.date) as entry_date, a.id as target_id, CONCAT(a.animal_name, CASE WHEN COALESCE(a.tag_number,'') = '' THEN '' ELSE CONCAT(' (',a.tag_number,')') END) as target_name, COALESCE(SUM(m.total_milk),0) as milk_quantity, COALESCE(SUM(m.total_milk * COALESCE(m.rate,0)),0) as milk_amount")
                ->get();

            $feedingRows = DB::table('feeding_records as f')
                ->join('animals as a', 'a.id', '=', 'f.animal_id')
                ->where('a.farmer_id', $farmerId)
                ->whereBetween(DB::raw('DATE(f.date)'), [$from->toDateString(), $to->toDateString()])
                ->when($targetId > 0, fn ($query) => $query->where('a.id', $targetId))
                ->groupBy(DB::raw('DATE(f.date)'), 'a.id', 'a.animal_name', 'a.tag_number')
                ->selectRaw("DATE(f.date) as entry_date, a.id as target_id, CONCAT(a.animal_name, CASE WHEN COALESCE(a.tag_number,'') = '' THEN '' ELSE CONCAT(' (',a.tag_number,')') END) as target_name, COALESCE(SUM(COALESCE(f.feeding_quantity, f.quantity, 0)),0) as feeding_quantity")
                ->get();
        }

        foreach ($milkRows as $row) {
            $key = $this->rowKey($row->entry_date, $row->target_id);
            if (! isset($rowsMap[$key])) {
                $rowsMap[$key] = $this->baseRow($row->entry_date, $row->target_id, (string) $row->target_name);
            }
            $rowsMap[$key]['milk_quantity'] = round((float) $row->milk_quantity, 2);
            $rowsMap[$key]['milk_amount'] = round((float) $row->milk_amount, 2);
        }

        foreach ($feedingRows as $row) {
            $key = $this->rowKey($row->entry_date, $row->target_id);
            if (! isset($rowsMap[$key])) {
                $rowsMap[$key] = $this->baseRow($row->entry_date, $row->target_id, (string) $row->target_name);
            }
            $rowsMap[$key]['feeding_quantity'] = round((float) $row->feeding_quantity, 2);
        }

        $rows = array_values($rowsMap);
        usort($rows, function (array $first, array $second): int {
            if ($first['date_key'] === $second['date_key']) {
                return strcmp((string) $first['target_name'], (string) $second['target_name']);
            }
            return strcmp((string) $second['date_key'], (string) $first['date_key']);
        });

        $totals = [
            'milk_quantity' => 0.0,
            'milk_amount' => 0.0,
            'feeding_quantity' => 0.0,
        ];
        foreach ($rows as $row) {
            $totals['milk_quantity'] += (float) ($row['milk_quantity'] ?? 0);
            $totals['milk_amount'] += (float) ($row['milk_amount'] ?? 0);
            $totals['feeding_quantity'] += (float) ($row['feeding_quantity'] ?? 0);
        }
        $totals = [
            'milk_quantity' => round($totals['milk_quantity'], 2),
            'milk_amount' => round($totals['milk_amount'], 2),
            'feeding_quantity' => round($totals['feeding_quantity'], 2),
        ];

        return response()->json([
            'status' => true,
            'message' => 'Report fetched successfully.',
            'data' => [
                'scope' => $scope,
                'target_id' => $targetId > 0 ? $targetId : null,
                'from_date' => $from->toDateString(),
                'to_date' => $to->toDateString(),
                'rows' => $rows,
                'totals' => $totals,
            ],
        ]);
    }

    public function profitLoss(Request $request, $farmer_id)
    {
        $farmerId = (int) $farmer_id;
        [$from, $to] = $this->resolveDateRange($request);

        $milkEarning = (float) DB::table('milk_productions as m')
            ->join('animals as a', 'a.id', '=', 'm.animal_id')
            ->where('a.farmer_id', $farmerId)
            ->whereBetween(DB::raw('DATE(m.date)'), [$from->toDateString(), $to->toDateString()])
            ->sum(DB::raw('m.total_milk * COALESCE(m.rate,0)'));

        $appointments = DoctorAppointment::query()
            ->where('farmer_id', $farmerId)
            ->whereIn('status', ['approved', 'in_progress', 'completed'])
            ->whereBetween(
                DB::raw('DATE(COALESCE(completed_at, accepted_at, requested_at, created_at))'),
                [$from->toDateString(), $to->toDateString()]
            )
            ->get(['fees', 'charges', 'on_site_medicine_charges']);

        $doctorCost = 0.0;
        $medicineCost = 0.0;
        foreach ($appointments as $appointment) {
            $fees = (float) ($appointment->fees ?? 0);
            $charges = (float) ($appointment->charges ?? 0);
            $doctorCost += $fees > 0 ? $fees : $charges;
            $medicineCost += (float) ($appointment->on_site_medicine_charges ?? 0);
        }

        $totalExpenses = $doctorCost + $medicineCost;
        $netProfit = $milkEarning - $totalExpenses;

        return response()->json([
            'status' => true,
            'message' => 'Profit & loss fetched successfully.',
            'data' => [
                'from_date' => $from->toDateString(),
                'to_date' => $to->toDateString(),
                'milk_earning' => round($milkEarning, 2),
                'doctor_cost' => round($doctorCost, 2),
                'medicine_cost' => round($medicineCost, 2),
                'total_expenses' => round($totalExpenses, 2),
                'net_profit' => round($netProfit, 2),
                'appointment_count' => $appointments->count(),
            ],
        ]);
    }

    private function resolveDateRange(Request $request): array
    {
        $to = $this->parseDate((string) $request->query('to_date')) ?? Carbon::today();
        $from = $this->parseDate((string) $request->query('from_date')) ?? $to->copy()->startOfMonth();
        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }
        return [$from->copy()->startOfDay(), $to->copy()->endOfDay()];
    }

    private function parseDate(string $raw): ?Carbon
    {
        $value = trim($raw);
        if ($value === '') {
            return null;
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value);
            } catch (\Throwable $exception) {
            }
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function rowKey(string $date, $targetId): string
    {
        return $date.'|'.(int) $targetId;
    }

    private function baseRow(string $date, $targetId, string $targetName): array
    {
        $dateObj = Carbon::parse($date);
        return [
            'date' => $dateObj->format('d/m/Y'),
            'date_key' => $dateObj->toDateString(),
            'target_id' => (int) $targetId,
            'target_name' => trim($targetName) !== '' ? $targetName : '-',
            'milk_quantity' => 0.0,
            'milk_amount' => 0.0,
            'feeding_quantity' => 0.0,
        ];
    }
}
