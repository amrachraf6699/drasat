<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\DashboardOrderResource;
use App\Http\Resources\Admin\DashboardProductResource;
use App\Http\Resources\Admin\DashboardTransferResource;
use App\Models\BankTransfer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $admin = $request->user('admin');
        $canViewProducts = $admin?->can('products.view') ?? false;
        $canViewOrders = $admin?->can('orders.view') ?? false;
        $canViewTransfers = $admin?->canAny(['transfers.view', 'transfers.review']) ?? false;
        $canViewUsers = $admin?->can('users.view') ?? false;
        $revenueCents = $canViewOrders ? Order::query()->where('status', 'paid')->sum('total_cents') : 0;

        $stats = [];

        if ($canViewProducts) {
            $stats[] = ['label' => __('admin.dashboard.stats.total_products'), 'label_ar' => __('admin.dashboard.stats.total_studies'), 'value' => Product::count(), 'change' => 12, 'tone' => 'emerald'];
        }

        if ($canViewOrders) {
            $stats[] = ['label' => __('admin.dashboard.stats.total_orders'), 'label_ar' => __('admin.dashboard.stats.all_orders'), 'value' => Order::count(), 'change' => 18, 'tone' => 'blue'];
            $stats[] = ['label' => __('admin.dashboard.stats.total_revenue'), 'label_ar' => __('admin.dashboard.stats.paid_revenue'), 'value' => number_format($revenueCents / 100, 2).' EGP', 'change' => 21, 'tone' => 'emerald'];
        }

        if ($canViewTransfers) {
            $stats[] = ['label' => __('admin.dashboard.stats.pending_transfers'), 'label_ar' => __('admin.dashboard.stats.needs_review'), 'value' => BankTransfer::where('status', 'pending')->count(), 'change' => 5, 'tone' => 'amber'];
        }

        if ($canViewUsers) {
            $stats[] = ['label' => __('admin.dashboard.stats.total_users'), 'label_ar' => __('admin.dashboard.stats.registered_users'), 'value' => number_format(User::count()), 'change' => 9, 'tone' => 'teal'];
        }

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentOrders' => $canViewOrders ? Order::query()
                ->with(['user', 'items'])
                ->latest()
                ->limit(5)
                ->get()
                ->pipe(fn ($orders) => DashboardOrderResource::collection($orders)->resolve($request)) : [],
            'bankTransfers' => $canViewTransfers ? BankTransfer::query()
                ->with(['user', 'order.user'])
                ->where('status', 'pending')
                ->latest()
                ->limit(5)
                ->get()
                ->pipe(fn ($transfers) => DashboardTransferResource::collection($transfers)->resolve($request)) : [],
            'productStatus' => $canViewProducts ? [
                ['label' => __('admin.dashboard.stats.total'), 'value' => Product::count(), 'tone' => 'slate'],
                ['label' => __('admin.dashboard.stats.active'), 'value' => Product::where('status', 'active')->count(), 'tone' => 'emerald'],
                ['label' => __('admin.dashboard.stats.inactive'), 'value' => Product::where('status', 'inactive')->count(), 'tone' => 'slate'],
                ['label' => __('admin.dashboard.stats.draft'), 'value' => Product::where('status', 'draft')->count(), 'tone' => 'amber'],
                ['label' => __('admin.dashboard.stats.documents'), 'value' => Product::withCount('documents')->get()->sum('documents_count'), 'tone' => 'blue'],
            ] : [],
            'products' => $canViewProducts ? Product::query()
                ->latest()
                ->limit(5)
                ->get()
                ->pipe(fn ($products) => DashboardProductResource::collection($products)->resolve($request)) : [],
        ]);
    }
}
