<?php

namespace App\Http\Middleware;

use App\Http\Resources\Admin\AuthenticatedAdminResource;
use App\Http\Resources\Admin\NotificationResource;
use App\Http\Resources\Admin\PendingTransferNotificationResource;
use App\Http\Resources\Storefront\CartResource;
use App\Http\Resources\Storefront\UserResource;
use App\Models\BankTransfer;
use App\Services\Storefront\CartService;
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
                'admin' => $admin ? AuthenticatedAdminResource::make($admin)->resolve($request) : null,
                'user' => $request->user()
                    ? fn () => UserResource::make($request->user())->resolve($request)
                    : null,
            ],
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
            ],
            'locale' => app()->getLocale(),
            'supportedLocales' => config('app.supported_locales', ['en', 'ar']),
            'direction' => app()->getLocale() === 'ar' ? 'rtl' : 'ltr',
            'translations' => Lang::get('admin'),
            'storefrontTranslations' => Lang::get('storefront'),
            'cartSummary' => fn () => Schema::hasTable('carts')
                ? CartResource::make(app(CartService::class)->summary($request))->resolve($request)
                : CartResource::make(null)->resolve($request),
            'adminNotifications' => [
                'unreadCount' => fn () => $notifications->count(),
                'items' => fn () => NotificationResource::collection($notifications)->resolve($request),
                'pendingTransferCount' => fn () => $pendingTransfers->count(),
                'pendingTransfers' => fn () => PendingTransferNotificationResource::collection($pendingTransfers)->resolve($request),
            ],
        ];
    }
}
