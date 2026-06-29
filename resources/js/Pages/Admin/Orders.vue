<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import IndexFilters from '@/Components/Admin/IndexFilters.vue';
import Pagination from '@/Components/Admin/Pagination.vue';
import { useTranslations } from '@/Composables/useTranslations';
import {
    CreditCard,
    Eye,
    ReceiptText,
    Landmark,
    CalendarDays,
    Package,
} from 'lucide-vue-next';

const props = defineProps({
    orders: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const { statusLabel, t } = useTranslations();
const orderItems = computed(() => props.orders.data || []);
const ordersTotal = computed(() => props.orders.meta?.total ?? props.orders.total ?? orderItems.value.length);
const filterFields = computed(() => [
    {
        key: 'status',
        label: t('common.all_statuses'),
        options: ['paid', 'pending', 'cancelled'].map((status) => ({ value: status, label: statusLabel(status) })),
    },
    {
        key: 'payment_method',
        label: t('common.all_methods'),
        options: [
            { value: 'paypal', label: 'PayPal' },
            { value: 'bank_transfer', label: paymentLabel('bank_transfer') },
        ],
    },
    {
        key: 'sort',
        label: t('common.sort_by'),
        options: [
            { value: 'newest', label: t('common.newest') },
            { value: 'oldest', label: t('common.oldest') },
            { value: 'total_high', label: t('common.total_high') },
            { value: 'total_low', label: t('common.total_low') },
        ],
    },
]);

function statusClass(status) {
    return {
        paid: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100',
        pending: 'bg-amber-50 text-amber-700 ring-1 ring-amber-100',
        cancelled: 'bg-red-50 text-red-700 ring-1 ring-red-100',
    }[status] || 'bg-slate-50 text-slate-600 ring-1 ring-slate-200';
}

function paymentIcon(method) {
    switch (method) {
        case 'bank_transfer':
            return Landmark;

        case 'paypal':
            return CreditCard;

        default:
            return CreditCard;
    }
}

function paymentLabel(method) {
    return method ? method.replaceAll('_', ' ') : 'N/A';
}

function isPaypal(method) {
    return method === 'paypal';
}
</script>

<template>
    <Head :title="t('orders.title')" />

    <AdminLayout>
        <div class="mb-6 flex items-end justify-between gap-4">
            <div>
                <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-950">
                    {{ t('orders.title') }}
                </h1>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white px-5 py-3 text-center shadow-sm">
                <p class="text-xs font-semibold text-slate-500">
                    {{ t('common.total') }}
                </p>

                <p class="text-2xl font-black text-slate-950">
                    {{ ordersTotal }}
                </p>
            </div>
        </div>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <IndexFilters action="/manage/orders" :filters="filters" :fields="filterFields" />

            <div class="overflow-x-auto lg:hidden">
                <table class="min-w-[820px] w-full text-start text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-3 text-start">{{ t('common.order') }}</th>
                            <th class="px-4 py-3 text-start">{{ t('common.customer') }}</th>
                            <th class="px-4 py-3 text-start">{{ t('common.items') }}</th>
                            <th class="px-4 py-3 text-start">{{ t('common.method') }}</th>
                            <th class="px-4 py-3 text-start">{{ t('common.total') }}</th>
                            <th class="px-4 py-3 text-start">{{ t('common.status') }}</th>
                            <th class="px-4 py-3 text-end">{{ t('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="order in orderItems" :key="`mobile-${order.id}`" class="hover:bg-slate-50/70">
                            <td class="whitespace-nowrap px-4 py-3 font-bold text-slate-950">{{ order.order_number }}</td>
                            <td class="max-w-[220px] px-4 py-3">
                                <p class="truncate font-semibold text-slate-700">{{ order.customer }}</p>
                                <p class="truncate text-xs text-slate-500">{{ order.email || t('common.no_email') }}</p>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-700">{{ order.items_count || 0 }}</td>
                            <td class="whitespace-nowrap px-4 py-3 capitalize text-slate-700">{{ paymentLabel(order.payment_method) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 font-black text-slate-950">{{ order.total }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold capitalize" :class="statusClass(order.status)">
                                    {{ statusLabel(order.status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-end">
                                <Link class="inline-flex h-9 items-center justify-center gap-2 rounded-md border border-emerald-200 px-3 text-sm font-semibold text-emerald-700 hover:bg-emerald-50" :href="`/manage/orders/${order.id}`">
                                    <Eye class="h-4 w-4" />
                                    {{ t('common.view') }}
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="hidden divide-y divide-slate-100 lg:block">
                <article
                    v-for="order in orderItems"
                    :key="order.id"
                    class="px-5 py-5 transition hover:bg-slate-50"
                >
                    <div class="grid gap-5 xl:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)_190px_130px_230px_110px] xl:items-center">
                        <div class="min-w-0">
                            <div class="flex items-start gap-3">
                                <div class="min-w-0">
                                    <p class="font-bold text-slate-950">
                                        {{ order.order_number }}
                                    </p>

                                    <p class="mt-1 truncate text-sm font-semibold text-slate-700">
                                        {{ order.customer }}
                                    </p>

                                    <p class="truncate text-xs font-medium text-slate-500">
                                        {{ order.email || t('common.no_email') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex min-w-0 items-start gap-3">
                            <Package class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />

                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-800">
                                    {{ order.items_count || 0 }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 rounded-full bg-slate-100 px-3 py-2 ring-1 ring-slate-100">
                            <div class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-white text-slate-700 ring-1 ring-slate-200">
                                <component
                                    :is="paymentIcon(order.payment_method)"
                                    class="h-4 w-4"
                                    :class="{
                                        'text-emerald-600': order.payment_method === 'paypal',
                                        'text-slate-700': order.payment_method === 'bank_transfer',
                                    }"
                                />
                            </div>

                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold capitalize text-slate-900">
                                    {{ paymentLabel(order.payment_method) }}
                                </p>
                            </div>
                        </div>

                        <div>
                            <p class="whitespace-nowrap text-base font-black text-slate-950">
                                {{ order.total }}
                            </p>
                        </div>

                        <div class="flex items-center gap-3 xl:justify-end">
                            <span
                                class="inline-flex min-w-[72px] items-center justify-center rounded-full px-3 py-1 text-xs font-bold capitalize"
                                :class="statusClass(order.status)"
                            >
                                {{ statusLabel(order.status) }}
                            </span>

                            <span class="inline-flex min-w-[125px] items-center justify-end gap-1 whitespace-nowrap text-xs font-semibold text-slate-500">
                                {{ order.created_at }}
                                <CalendarDays class="h-3.5 w-3.5 shrink-0" />
                            </span>
                        </div>

                        <div class="flex justify-end">
                            <Link class="inline-flex h-9 items-center justify-center gap-2 rounded-md border border-emerald-200 px-3 text-sm font-semibold text-emerald-700 hover:bg-emerald-50" :href="`/manage/orders/${order.id}`">
                                <Eye class="h-4 w-4" />
                                {{ t('common.view') }}
                            </Link>
                        </div>
                    </div>
                </article>

                <div
                    v-if="orderItems.length === 0"
                    class="px-5 py-16 text-center"
                >
                    <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100">
                        <ReceiptText class="h-6 w-6" />
                    </div>

                    <p class="mt-4 text-sm font-semibold text-slate-600">
                        {{ t('orders.empty') }}
                    </p>
                </div>
            </div>

            <div v-if="orderItems.length === 0" class="px-5 py-16 text-center lg:hidden">
                <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100">
                    <ReceiptText class="h-6 w-6" />
                </div>

                <p class="mt-4 text-sm font-semibold text-slate-600">
                    {{ t('orders.empty') }}
                </p>
            </div>

            <Pagination :paginator="orders" />
        </section>
    </AdminLayout>
</template>
