<script setup>
import { computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useTranslations } from '@/Composables/useTranslations';
import {
    Check,
    Clock3,
    DollarSign,
    Edit3,
    FileUp,
    PackageCheck,
    PlusCircle,
    ReceiptText,
    ShoppingCart,
    UsersRound,
    WalletCards,
    X,
} from 'lucide-vue-next';

defineProps({
    stats: { type: Array, required: true },
    recentOrders: { type: Array, required: true },
    bankTransfers: { type: Array, required: true },
    productStatus: { type: Array, required: true },
    products: { type: Array, required: true },
});

const { statusLabel, t } = useTranslations();
const page = usePage();
const permissions = computed(() => page.props.auth?.admin?.permissions || []);
const statIcons = [PackageCheck, ShoppingCart, Clock3, DollarSign, UsersRound];

const toneClasses = {
    emerald: 'bg-emerald-50 text-emerald-700',
    teal: 'bg-teal-50 text-teal-700',
    blue: 'bg-blue-50 text-blue-700',
    amber: 'bg-amber-50 text-amber-700',
    red: 'bg-red-50 text-red-700',
    slate: 'bg-slate-50 text-slate-700',
};

function statusClass(status) {
    const normalized = String(status).toLowerCase();
    return {
        paid: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100',
        active: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100',
        pending: 'bg-amber-50 text-amber-700 ring-1 ring-amber-100',
        draft: 'bg-amber-50 text-amber-700 ring-1 ring-amber-100',
        cancelled: 'bg-red-50 text-red-700 ring-1 ring-red-100',
        inactive: 'bg-slate-100 text-slate-600 ring-1 ring-slate-200',
    }[normalized] || 'bg-slate-50 text-slate-600 ring-1 ring-slate-200';
}

function approveTransfer(id) {
    router.patch(`/manage/bank-transfers/${id}/approve`, {}, { preserveScroll: true });
}

function rejectTransfer(id) {
    router.patch(`/manage/bank-transfers/${id}/reject`, {}, { preserveScroll: true });
}

function canAny(requiredPermissions) {
    return requiredPermissions.some((permission) => permissions.value.includes(permission));
}
</script>

<template>
    <Head :title="t('dashboard.title')" />

    <AdminLayout>
        <div class="mb-6">
            <p class="text-2xl font-semibold text-slate-950">{{ t('dashboard.title') }}</p>
        </div>

        <section class="grid min-w-0 gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <article
                v-for="(stat, index) in stats"
                :key="stat.label"
                class="min-w-0 rounded-lg border border-slate-200 bg-white p-5 shadow-sm"
            >
                <div class="mb-6 flex items-start justify-between gap-3">
                    <div :class="toneClasses[stat.tone]" class="grid h-12 w-12 shrink-0 place-items-center rounded-lg">
                        <component :is="statIcons[index]" class="h-6 w-6" stroke-width="1.9" />
                    </div>
                    <div class="min-w-0 text-end">
                        <p class="break-words text-sm font-medium text-slate-700">{{ stat.label }}</p>
                        <p class="break-words text-xs text-slate-500">{{ stat.label_ar }}</p>
                    </div>
                </div>

                <div class="flex items-end justify-between gap-3">
                    <div class="min-w-0">
                        <p class="break-words text-2xl font-semibold tracking-normal text-slate-950">{{ stat.value }}</p>
                        <p class="mt-2 text-xs leading-5 text-slate-500">{{ t('dashboard.vs_last_month') }}</p>
                    </div>
                    <span class="shrink-0 text-sm font-semibold" :class="stat.tone === 'amber' ? 'text-amber-600' : 'text-emerald-600'">+ {{ stat.change }}%</span>
                </div>
            </article>
        </section>

        <section class="mt-6 grid min-w-0 gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,0.94fr)]">
            <article v-if="canAny(['orders.view'])" class="min-w-0 rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
                    <div class="min-w-0">
                        <h2 class="font-semibold text-slate-950">{{ t('dashboard.recent_orders') }}</h2>
                    </div>
                    <Link class="shrink-0 text-sm font-semibold text-emerald-700" href="/manage/orders">{{ t('dashboard.view_all') }}</Link>
                </div>

                <div class="divide-y divide-slate-100">
                    <article v-for="order in recentOrders" :key="order.number" class="min-w-0 px-5 py-4 transition hover:bg-slate-50/70">
                        <div class="grid min-w-0 gap-4 lg:grid-cols-[minmax(0,1fr)_120px_130px] lg:items-center">
                            <div class="flex min-w-0 items-start gap-3">
                                <div class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-blue-50 text-blue-700">
                                    <ReceiptText class="h-5 w-5" />
                                </div>
                                <div class="min-w-0">
                                    <div class="flex min-w-0 flex-wrap items-center gap-2">
                                        <p class="break-words font-semibold text-slate-900">{{ order.number }}</p>
                                        <span class="rounded-md px-2 py-1 text-xs font-semibold" :class="statusClass(order.status)">
                                            {{ statusLabel(order.status) }}
                                        </span>
                                    </div>
                                    <p class="mt-1 break-words text-sm text-slate-600">{{ order.customer }}</p>
                                </div>
                            </div>

                            <div>
                                <p class="whitespace-nowrap text-sm font-semibold text-slate-900">{{ order.amount }}</p>
                            </div>

                            <div>
                                <p class="text-sm font-medium text-slate-700">{{ order.date }}</p>
                            </div>
                        </div>
                    </article>

                    <div v-if="recentOrders.length === 0" class="px-5 py-8 text-sm text-slate-500">
                        {{ t('dashboard.no_orders') }}
                    </div>
                </div>
            </article>

            <article v-if="canAny(['transfers.view', 'transfers.review'])" class="min-w-0 rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
                    <div class="min-w-0">
                        <h2 class="font-semibold text-slate-950">{{ t('dashboard.pending_transfers') }}</h2>
                    </div>
                    <Link class="shrink-0 text-sm font-semibold text-emerald-700" href="/manage/bank-transfers">{{ t('dashboard.view_all') }}</Link>
                </div>

                <div class="divide-y divide-slate-100">
                    <div v-for="transfer in bankTransfers" :key="transfer.reference" class="grid min-w-0 gap-4 px-5 py-4 md:grid-cols-[minmax(0,1fr)_140px_auto] md:items-center">
                        <div class="min-w-0">
                            <p class="break-words text-sm font-medium text-slate-700">{{ transfer.customer }}</p>
                            <p class="break-words text-xs text-slate-500">{{ transfer.reference }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ transfer.date }}</p>
                        </div>
                        <div>
                            <p class="whitespace-nowrap text-sm font-semibold text-slate-900">{{ transfer.amount }}</p>
                            <p class="text-xs text-slate-500">{{ t('common.amount') }}</p>
                        </div>
                        <div class="flex items-center gap-2 md:justify-end">
                            <button v-if="canAny(['transfers.approve', 'transfers.review'])" class="grid h-9 w-9 place-items-center rounded-md border border-emerald-200 text-emerald-700 hover:bg-emerald-50" type="button" @click="approveTransfer(transfer.id)">
                                <Check class="h-4 w-4" />
                            </button>
                            <button v-if="canAny(['transfers.reject', 'transfers.review'])" class="grid h-9 w-9 place-items-center rounded-md border border-red-200 text-red-600 hover:bg-red-50" type="button" @click="rejectTransfer(transfer.id)">
                                <X class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <div v-if="bankTransfers.length === 0" class="px-5 py-8 text-sm text-slate-500">
                        {{ t('dashboard.no_pending_transfers') }}
                    </div>
                </div>
            </article>
        </section>

        <section class="mt-6 grid min-w-0 gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,0.94fr)]">
            <article v-if="canAny(['products.view'])" class="min-w-0 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <h2 class="font-semibold text-slate-950">{{ t('dashboard.products_by_status') }}</h2>
                    </div>
                    <Link class="shrink-0 text-sm font-semibold text-emerald-700" href="/manage/products">{{ t('dashboard.view_all') }}</Link>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
                    <div v-for="item in productStatus" :key="item.label" class="min-w-0 rounded-lg border border-slate-200 p-3 text-center">
                        <p class="break-words text-xs text-slate-500">{{ item.label }}</p>
                        <p class="mt-1 text-xl font-semibold text-slate-950">{{ item.value }}</p>
                    </div>
                </div>

                <div class="mt-5 divide-y divide-slate-100 rounded-lg border border-slate-200">
                    <div v-for="product in products" :key="product.id" class="grid min-w-0 gap-3 px-4 py-3 sm:grid-cols-[minmax(0,1fr)_120px_auto] sm:items-center">
                        <div class="min-w-0">
                            <p class="break-words text-sm font-medium text-slate-800">{{ product.title }}</p>
                            <p class="text-xs text-slate-500">{{ t('dashboard.product') }}</p>
                        </div>
                        <div>
                            <p class="whitespace-nowrap text-sm font-medium text-slate-700">{{ product.price }}</p>
                            <p class="text-xs text-slate-500">{{ t('common.price') }}</p>
                        </div>
                        <div class="flex items-center gap-2 sm:justify-end">
                            <span class="inline-flex rounded-md px-3 py-1 text-xs font-medium" :class="statusClass(product.status)">
                                {{ statusLabel(product.status) }}
                            </span>
                            <Link href="/manage/products" class="text-slate-500 hover:text-emerald-700"><Edit3 class="h-5 w-5" /></Link>
                        </div>
                    </div>

                    <div v-if="products.length === 0" class="px-4 py-8 text-sm text-slate-500">
                        {{ t('dashboard.no_products') }}
                    </div>
                </div>
            </article>

            <article v-if="canAny(['products.view', 'transfers.view', 'transfers.review', 'users.view'])" class="min-w-0 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-6 text-start sm:text-end">
                    <h2 class="font-semibold text-slate-950">{{ t('dashboard.quick_actions') }}</h2>
                </div>

                <div class="grid min-w-0 gap-4 sm:grid-cols-2">
                    <Link v-if="canAny(['products.view'])" class="group min-w-0 rounded-lg border border-slate-200 p-5 text-center transition hover:border-emerald-200 hover:bg-emerald-50/40" href="/manage/products">
                        <PlusCircle class="mx-auto h-10 w-10 text-emerald-700" />
                        <p class="mt-4 text-sm font-semibold text-emerald-800">{{ t('dashboard.add_product') }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ t('dashboard.create_study') }}</p>
                    </Link>
                    <Link v-if="canAny(['products.view'])" class="group min-w-0 rounded-lg border border-slate-200 p-5 text-center transition hover:border-blue-200 hover:bg-blue-50/40" href="/manage/products">
                        <FileUp class="mx-auto h-10 w-10 text-blue-600" />
                        <p class="mt-4 text-sm font-semibold text-blue-700">{{ t('dashboard.upload_media') }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ t('dashboard.attach_files') }}</p>
                    </Link>
                    <Link v-if="canAny(['transfers.view', 'transfers.review'])" class="group min-w-0 rounded-lg border border-slate-200 p-5 text-center transition hover:border-amber-200 hover:bg-amber-50/40" href="/manage/bank-transfers">
                        <WalletCards class="mx-auto h-10 w-10 text-amber-600" />
                        <p class="mt-4 text-sm font-semibold text-amber-700">{{ t('dashboard.review_transfers') }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ t('dashboard.approve_payments') }}</p>
                    </Link>
                    <Link v-if="canAny(['users.view'])" class="group min-w-0 rounded-lg border border-slate-200 p-5 text-center transition hover:border-teal-200 hover:bg-teal-50/40" href="/manage/users">
                        <UsersRound class="mx-auto h-10 w-10 text-teal-700" />
                        <p class="mt-4 text-sm font-semibold text-teal-800">{{ t('dashboard.manage_users') }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ t('dashboard.view_customers') }}</p>
                    </Link>
                </div>
            </article>
        </section>
    </AdminLayout>
</template>
