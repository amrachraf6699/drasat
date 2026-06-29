<?php

namespace App\Http\Middleware;

use App\Models\BankTransfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Schema;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $admin = $request->user('admin');
        $pendingTransfers = collect();
        $notifications = collect();

        if ($admin && $admin->canAny(['transfers.view', 'transfers.review']) && Schema::hasTable('bank_transfers')) {
            $pendingTransfers = BankTransfer::query()
                ->with(['user', 'order.user'])
                ->where('status', 'pending')
                ->latest()
                ->limit(5)
                ->get();
        }

        if ($admin && Schema::hasTable('notifications')) {
            $notifications = $admin->unreadNotifications()
                ->latest()
                ->limit(5)
                ->get();
        }

        return [
            ...parent::share($request),
            'auth' => [
                'admin' => $admin ? [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'roles' => $admin->getRoleNames()->values(),
                    'permissions' => $admin->getAllPermissions()->pluck('name')->values(),
                ] : null,
            ],
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
            ],
            'locale' => app()->getLocale(),
            'supportedLocales' => config('app.supported_locales', ['en', 'ar']),
            'direction' => app()->getLocale() === 'ar' ? 'rtl' : 'ltr',
            'translations' => Lang::get('admin'),
            'adminNotifications' => [
                'unreadCount' => fn () => $notifications->count(),
                'items' => fn () => $notifications->map(fn ($notification) => [
                    'id' => $notification->id,
                    'title' => $notification->data['title'] ?? __('admin.layout.notification'),
                    'body' => $notification->data['body'] ?? null,
                    'href' => $notification->data['href'] ?? null,
                    'created_at' => $notification->created_at?->diffForHumans(),
                ])->values(),
                'pendingTransferCount' => fn () => $pendingTransfers->count(),
                'pendingTransfers' => fn () => $pendingTransfers->map(fn (BankTransfer $transfer) => [
                    'id' => $transfer->id,
                    'title' => __('admin.layout.pending_transfer_title'),
                    'body' => __('admin.layout.pending_transfer_body', [
                        'reference' => $transfer->reference_number ?: __('admin.common.no_reference'),
                        'customer' => $transfer->user?->name ?? $transfer->order?->user?->name ?? __('admin.common.guest'),
                    ]),
                    'href' => route('admin.bank-transfers.index'),
                    'created_at' => $transfer->created_at?->diffForHumans(),
                ])->values(),
            ],
        ];
    }
}
