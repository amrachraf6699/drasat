<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\UserDetailResource;
use App\Http\Resources\Admin\UserResource;
use App\Models\BankTransfer;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['q', 'purchases', 'sort']);
        $query = User::query()->withCount('purchases');

        $query
            ->when($filters['q'] ?? null, function ($query, string $search) {
                $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->when(($filters['purchases'] ?? null) === 'with', fn ($query) => $query->has('purchases'))
            ->when(($filters['purchases'] ?? null) === 'without', fn ($query) => $query->doesntHave('purchases'));

        match ($filters['sort'] ?? 'newest') {
            'oldest' => $query->oldest(),
            'name_az' => $query->orderBy('name'),
            'name_za' => $query->orderByDesc('name'),
            default => $query->latest(),
        };

        return Inertia::render('Admin/Users', [
            'filters' => $filters,
            'users' => UserResource::collection($query->paginate(10)->withQueryString()),
        ]);
    }

    public function show(Request $request, User $user): Response
    {
        $user->load([
            'orders.items',
            'purchases.order',
            'purchases.product',
        ]);

        $transfers = BankTransfer::query()
            ->with(['order'])
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return Inertia::render('Admin/Detail', (new UserDetailResource($user, $transfers))->resolve($request));
    }
}
