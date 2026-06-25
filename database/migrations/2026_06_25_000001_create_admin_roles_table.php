<?php

use App\Services\AdminAccess;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->json('permissions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('password')->constrained('admin_roles')->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('role_id');
        });

        $now = now();
        $allPermissions = AdminAccess::allPermissionKeys();

        $adminRoleId = DB::table('admin_roles')->insertGetId([
            'name' => 'Admin',
            'slug' => 'admin',
            'description' => 'Full access to the admin panel.',
            'permissions' => json_encode($allPermissions),
            'is_active' => true,
            'is_system' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('admin_roles')->insert([
            'name' => 'Counsellor',
            'slug' => 'counsellor',
            'description' => 'Default counsellor role. Permissions can be customized later.',
            'permissions' => json_encode([
                'dashboard.view',
                'farmer_list.view',
                'doctor_appointments.view',
                'doctor_appointments.assign',
                'doctor_visited.view',
            ]),
            'is_active' => true,
            'is_system' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('users')
            ->whereNull('role_id')
            ->update([
                'role_id' => $adminRoleId,
                'is_active' => true,
            ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
            $table->dropColumn('is_active');
        });

        Schema::dropIfExists('admin_roles');
    }
};
