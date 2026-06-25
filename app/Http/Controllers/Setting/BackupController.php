<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackupController extends Controller
{
    public function index()
    {
        $tables = collect(Schema::getTableListing())
            ->sort()
            ->values();

        $tableSummaries = $tables->map(function (string $table) {
            return [
                'name' => $table,
                'rows' => DB::table($table)->count(),
            ];
        });

        return view('settings.backup', [
            'tableSummaries' => $tableSummaries,
            'generatedAt' => now(),
        ]);
    }

    public function download()
    {
        $tables = collect(Schema::getTableListing())
            ->sort()
            ->values();

        $payload = [
            'meta' => [
                'app' => config('app.name', 'Laravel'),
                'generated_at' => now()->toDateTimeString(),
                'generated_at_iso' => now()->toIso8601String(),
                'table_count' => $tables->count(),
            ],
            'tables' => [],
        ];

        foreach ($tables as $table) {
            $query = DB::table($table);
            if (Schema::hasColumn($table, 'id')) {
                $query->orderBy('id');
            }

            $payload['tables'][$table] = [
                'count' => DB::table($table)->count(),
                'rows' => $query->get()->map(fn ($row) => (array) $row)->all(),
            ];
        }

        $fileName = 'corzin-backup-'.now()->format('Y-m-d_H-i-s').'.json';

        return response()->streamDownload(function () use ($payload) {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }, $fileName, [
            'Content-Type' => 'application/json; charset=UTF-8',
        ]);
    }
}
