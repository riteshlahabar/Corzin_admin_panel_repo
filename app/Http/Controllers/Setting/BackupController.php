<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\BackupDownload;
use DateTimeInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackupController extends Controller
{
    public function index()
    {
        return view('settings.backup', [
            'backupDownloads' => BackupDownload::query()
                ->with('user')
                ->latest('downloaded_at')
                ->latest('id')
                ->paginate($this->tablePerPage(request()))
                ->withQueryString(),
        ]);
    }

    public function download()
    {
        $tables = collect(Schema::getTableListing())
            ->sort()
            ->values();

        $sql = [];
        $sql[] = '-- Corzin SQL Backup';
        $sql[] = '-- Generated At: ' . now()->toDateTimeString();
        $sql[] = '-- App: ' . config('app.name', 'Laravel');
        $sql[] = 'SET FOREIGN_KEY_CHECKS=0;';
        $sql[] = '';

        foreach ($tables as $table) {
            $sql[] = '-- ----------------------------';
            $sql[] = '-- Table structure for `' . $table . '`';
            $sql[] = '-- ----------------------------';
            $sql[] = 'DROP TABLE IF EXISTS `' . $table . '`;';

            $createRow = (array) (DB::select('SHOW CREATE TABLE `' . str_replace('`', '``', $table) . '`')[0] ?? []);
            $createStatement = '';
            foreach ($createRow as $key => $value) {
                if (stripos((string) $key, 'Create Table') !== false) {
                    $createStatement = (string) $value;
                    break;
                }
            }
            if ($createStatement !== '') {
                $sql[] = $createStatement . ';';
            }
            $sql[] = '';

            $query = DB::table($table);
            if (Schema::hasColumn($table, 'id')) {
                $query->orderBy('id');
            }

            $rows = $query->get()->map(fn ($row) => (array) $row)->all();
            if (empty($rows)) {
                continue;
            }

            $columns = Schema::getColumnListing($table);
            $columnSql = '`' . implode('`, `', $columns) . '`';
            $sql[] = '-- Dumping data for table `' . $table . '`';

            foreach ($rows as $row) {
                $values = [];
                foreach ($columns as $column) {
                    $values[] = $this->sqlValue($row[$column] ?? null);
                }

                $sql[] = 'INSERT INTO `' . $table . '` (' . $columnSql . ') VALUES (' . implode(', ', $values) . ');';
            }

            $sql[] = '';
        }

        $sql[] = 'SET FOREIGN_KEY_CHECKS=1;';

        $content = implode("\n", $sql) . "\n";
        $fileName = 'corzin-backup-' . now()->format('Y-m-d_H-i-s') . '.sql';

        BackupDownload::create([
            'downloaded_by' => Auth::id(),
            'file_name' => $fileName,
            'backup_format' => 'sql',
            'tables_count' => $tables->count(),
            'file_size_bytes' => strlen($content),
            'downloaded_at' => now(),
            'notes' => 'Database SQL backup downloaded from admin panel.',
        ]);

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $fileName, [
            'Content-Type' => 'application/sql; charset=UTF-8',
        ]);
    }

    private function sqlValue(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if ($value instanceof DateTimeInterface) {
            return DB::connection()->getPdo()->quote($value->format('Y-m-d H:i:s'));
        }

        if (is_array($value) || is_object($value)) {
            return DB::connection()->getPdo()->quote(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return DB::connection()->getPdo()->quote((string) $value);
    }
}
