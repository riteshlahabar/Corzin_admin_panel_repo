<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\AdminRole;
use App\Services\AdminAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminRoleController extends Controller
{
    public function index(Request $request)
    {
        $query = AdminRole::query()->latest('is_system')->latest('id');

        if ($request->filled('search')) {
            $search = strtolower(trim((string) $request->search));
            $query->where(function ($builder) use ($search) {
                $builder->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(slug) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(description) LIKE ?', ["%{$search}%"]);
            });
        }

        $roles = $query->paginate($this->tablePerPage($request))->withQueryString();
        $permissionGroups = AdminAccess::groups();

        return view('settings.roles', compact('roles', 'permissionGroups'));
    }

    public function create()
    {
        $permissionGroups = AdminAccess::groups();

        return view('settings.role_create', compact('permissionGroups'));
    }

    public function store(Request $request)
    {
        $data = $this->validateRole($request);
        $permissions = AdminAccess::normalizePermissions($request->input('permissions', []));

        AdminRole::create([
            'name' => trim($data['name']),
            'slug' => $this->uniqueSlug($data['slug'] ?? $data['name']),
            'description' => trim((string) ($data['description'] ?? '')),
            'permissions' => $permissions,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'is_system' => false,
        ]);

        return redirect()
            ->route('settings.roles.index')
            ->with('success', 'Role created successfully.');
    }

    public function update(Request $request, AdminRole $role)
    {
        $data = $this->validateRole($request, $role);
        $permissions = AdminAccess::normalizePermissions($request->input('permissions', []));

        if ($role->is_system && $role->slug === 'admin') {
            $permissions = AdminAccess::allPermissionKeys();
        }

        $role->update([
            'name' => trim($data['name']),
            'slug' => $this->uniqueSlug($data['slug'] ?? $data['name'], $role->id),
            'description' => trim((string) ($data['description'] ?? '')),
            'permissions' => $permissions,
            'is_active' => $role->is_system && $role->slug === 'admin'
                ? true
                : (bool) ($data['is_active'] ?? false),
        ]);

        return back()->with('success', 'Role updated successfully.');
    }

    public function toggle(AdminRole $role)
    {
        if ($role->is_system && $role->slug === 'admin') {
            return back()->with('error', 'Admin role cannot be disabled.');
        }

        $role->update([
            'is_active' => ! $role->is_active,
        ]);

        return back()->with('success', 'Role status updated successfully.');
    }

    protected function validateRole(Request $request, ?AdminRole $role = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => [
                'nullable',
                'string',
                'max:120',
                Rule::unique('admin_roles', 'slug')->ignore($role?->id),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ]);
    }

    protected function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value);
        if ($base === '') {
            $base = 'role';
        }

        $slug = $base;
        $suffix = 2;

        while (
            AdminRole::query()
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
