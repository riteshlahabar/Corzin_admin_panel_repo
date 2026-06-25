<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('downloaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('file_name');
            $table->string('backup_format', 20)->default('sql');
            $table->unsignedInteger('tables_count')->default(0);
            $table->unsignedBigInteger('file_size_bytes')->default(0);
            $table->timestamp('downloaded_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_downloads');
    }
};
