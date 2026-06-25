<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\AdminRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()
            ->with('role')
            ->latest('id');

        if ($request->filled('search')) {
            $search = strtolower(trim((string) $request->search));
            $query->where(function ($builder) use ($search) {
                $builder->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"]);
            });
        }

        $users = $query->paginate($this->tablePerPage($request))->withQueryString();
        $roles = AdminRole::query()->where('is_active', true)->orderBy('name')->get();

        return view('settings.users', compact('users', 'roles'));
    }

    public function store(Request $request)
    {
        $data = $this->validateUser($request);

        User::create([
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'password' => $data['password'],
            'role_id' => (int) $data['role_id'],
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return back()->with('success', 'Admin user created successfully.');
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validateUser($request, $user);

        if ((int) $user->id === (int) $request->user()->id && ! (bool) ($data['is_active'] ?? true)) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $payload = [
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'role_id' => (int) $data['role_id'],
            'is_active' => (bool) ($data['is_active'] ?? false),
        ];

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $user->update($payload);

        return back()->with('success', 'Admin user updated successfully.');
    }

    public function toggle(Request $request, User $user)
    {
        if ((int) $user->id === (int) $request->user()->id) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        return back()->with('success', 'Admin user status updated successfully.');
    }

    protected function validateUser(Request $request, ?User $user = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'email',
                'max:190',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'role_id' => ['required', 'exists:admin_roles,id'],
            'is_active' => ['nullable', 'boolean'],
        ];

        if ($user) {
            $rules['password'] = ['nullable', 'string', 'min:6'];
        } else {
            $rules['password'] = ['required', 'string', 'min:6'];
        }

        return $request->validate($rules);
    }
}
