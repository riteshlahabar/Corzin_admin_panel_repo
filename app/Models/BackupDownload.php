<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupDownload extends Model
{
    protected $fillable = [
        'downloaded_by',
        'file_name',
        'backup_format',
        'tables_count',
        'file_size_bytes',
        'downloaded_at',
        'notes',
    ];

    protected $casts = [
        'downloaded_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'downloaded_by');
    }
}
