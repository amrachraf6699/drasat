<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\RoleDetailResource;
use App\Http\Resources\Admin\RoleResource;
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
            'roles' => RoleResource::collection($query->paginate(10)->withQueryString()),
        ]);
    }

    public function show(Request $request, Role $role): Response
    {
        $this->ensureAdminRole($role);

        $role->load('permissions');
        $admins = Admin::query()
            ->role($role->name, 'admin')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Detail', (new RoleDetailResource($role, $admins))->resolve($request));
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

    private function ensureAdminRole(Role $role): void
    {
        abort_unless($role->guard_name === 'admin', 404);
    }
}
