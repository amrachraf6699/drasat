<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminAccountResource;
use App\Http\Resources\Admin\AdminDetailResource;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class AdminController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['q', 'status', 'role', 'sort']);
        $query = Admin::query()->where('id', '<>', auth()->id())->with('roles');

        $query
            ->when($filters['q'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when(($filters['status'] ?? null) === 'active', fn ($query) => $query->where('is_active', true))
            ->when(($filters['status'] ?? null) === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($filters['role'] ?? null, fn ($query, string $role) => $query->role($role, 'admin'));

        match ($filters['sort'] ?? 'newest') {
            'oldest' => $query->oldest(),
            'name_az' => $query->orderBy('name'),
            'name_za' => $query->orderByDesc('name'),
            default => $query->latest(),
        };

        return Inertia::render('Admin/Admins', [
            'filters' => $filters,
            'filterOptions' => [
                'roles' => $this->roles(),
            ],
            'admins' => AdminAccountResource::collection($query->paginate(10)->withQueryString()),
        ]);
    }

    public function show(Request $request, Admin $admin): Response
    {
        $admin->load(['roles.permissions', 'permissions']);

        return Inertia::render('Admin/Detail', AdminDetailResource::make($admin)->resolve($request));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $admin = Admin::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'is_active' => (bool) ($data['is_active'] ?? true),
            'email_verified_at' => now(),
        ]);

        $admin->syncRoles($data['roles'] ?? []);

        return back()->with('status', __('admin.flash.admin_created'));
    }

    public function update(Request $request, Admin $admin): RedirectResponse
    {
        $data = $this->validated($request, $admin);

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'is_active' => (bool) ($data['is_active'] ?? false),
        ];

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $admin->update($payload);
        $admin->syncRoles($data['roles'] ?? []);

        return back()->with('status', __('admin.flash.admin_updated'));
    }

    public function destroy(Request $request, Admin $admin): RedirectResponse
    {
        if ($request->user('admin')?->is($admin)) {
            return back()->with('status', __('admin.flash.admin_self_delete_blocked'));
        }

        $admin->delete();

        return back()->with('status', __('admin.flash.admin_deleted'));
    }

    private function validated(Request $request, ?Admin $admin = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('admins', 'email')->ignore($admin)],
            'password' => [$admin ? 'nullable' : 'required', 'string', 'min:8'],
            'is_active' => ['nullable', 'boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')->where('guard_name', 'admin')],
        ]);
    }

    private function roles(): array
    {
        return Role::query()
            ->where('guard_name', 'admin')
            ->orderBy('name')
            ->pluck('name')
            ->values()
            ->all();
    }
}
