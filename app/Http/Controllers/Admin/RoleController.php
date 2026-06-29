<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['q', 'permission', 'sort']);
        $query = Role::query()
            ->where('guard_name', 'admin')
            ->with('permissions');

        $query
            ->when($filters['q'] ?? null, function ($query, string $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when($filters['permission'] ?? null, function ($query, string $permission) {
                $query->whereHas('permissions', fn ($query) => $query->where('name', $permission));
            });

        match ($filters['sort'] ?? 'newest') {
            'oldest' => $query->oldest(),
            'name_az' => $query->orderBy('name'),
            'name_za' => $query->orderByDesc('name'),
            default => $query->latest(),
        };

        return Inertia::render('Admin/Roles', [
            'filters' => $filters,
            'filterOptions' => [
                'permissions' => $this->permissions(),
            ],
            'roles' => $query
                ->paginate(10)
                ->withQueryString()
                ->through(fn (Role $role) => $this->serializeRole($role)),
        ]);
    }

    public function show(Role $role): Response
    {
        $this->ensureAdminRole($role);

        $role->load('permissions');
        $admins = Admin::query()
            ->role($role->name, 'admin')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Detail', [
            'title' => $role->name,
            'subtitle' => __('admin.roles.subtitle'),
            'backHref' => route('admin.roles.index'),
            'stats' => [
                ['label' => __('admin.common.permissions'), 'value' => $role->permissions->count()],
                ['label' => __('admin.roles.assigned_admins'), 'value' => $admins->count()],
                ['label' => __('admin.common.created'), 'value' => $role->created_at?->format('Y-m-d H:i')],
                ['label' => __('admin.common.updated'), 'value' => $role->updated_at?->format('Y-m-d H:i')],
            ],
            'fields' => [
                ['label' => __('admin.common.name'), 'value' => $role->name],
                ['label' => __('admin.common.guard'), 'value' => $role->guard_name],
                ['label' => __('admin.common.permissions'), 'value' => $role->permissions->pluck('name')->join(', ') ?: '-'],
            ],
            'sections' => [
                [
                    'title' => __('admin.common.permissions'),
                    'columns' => [
                        ['key' => 'name', 'label' => __('admin.common.name')],
                    ],
                    'rows' => $role->permissions->map(fn (Permission $permission) => [
                        'id' => $permission->id,
                        'name' => $permission->name,
                    ])->values(),
                ],
                [
                    'title' => __('admin.roles.assigned_admins'),
                    'columns' => [
                        ['key' => 'name', 'label' => __('admin.common.name')],
                        ['key' => 'email', 'label' => __('admin.common.email')],
                    ],
                    'rows' => $admins->map(fn (Admin $admin) => [
                        'id' => $admin->id,
                        'name' => $admin->name,
                        'email' => $admin->email,
                        'href' => route('admin.admins.show', $admin),
                    ])->values(),
                    'showLinks' => true,
                ],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => 'admin',
        ]);

        $role->syncPermissions($data['permissions'] ?? []);

        return back()->with('status', __('admin.flash.role_created'));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->ensureAdminRole($role);

        $data = $this->validated($request, $role);

        $role->update(['name' => $data['name']]);
        $role->syncPermissions($data['permissions'] ?? []);

        return back()->with('status', __('admin.flash.role_updated'));
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->ensureAdminRole($role);

        if ($role->name === 'super-admin' || Admin::query()->role($role->name, 'admin')->exists()) {
            return back()->with('status', __('admin.flash.role_delete_blocked'));
        }

        $role->delete();

        return back()->with('status', __('admin.flash.role_deleted'));
    }

    private function validated(Request $request, ?Role $role = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')
                    ->where('guard_name', 'admin')
                    ->ignore($role),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')->where('guard_name', 'admin')],
        ]);
    }

    private function permissions(): array
    {
        return Permission::query()
            ->where('guard_name', 'admin')
            ->orderBy('name')
            ->pluck('name')
            ->values()
            ->all();
    }

    private function serializeRole(Role $role): array
    {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name')->values(),
            'permissions_count' => $role->permissions->count(),
            'admins_count' => Admin::query()->role($role->name, 'admin')->count(),
            'created_at' => $role->created_at?->format('Y-m-d H:i'),
        ];
    }

    private function ensureAdminRole(Role $role): void
    {
        abort_unless($role->guard_name === 'admin', 404);
    }
}
