<script setup>
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import IndexFilters from '@/Components/Admin/IndexFilters.vue';
import Pagination from '@/Components/Admin/Pagination.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { Check, Eye, Landmark, X } from 'lucide-vue-next';

const props = defineProps({
    transfers: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const { statusLabel, t } = useTranslations();
const transferItems = computed(() => props.transfers.data || []);
const transfersTotal = computed(() => props.transfers.meta?.total ?? props.transfers.total ?? transferItems.value.length);
const filterFields = computed(() => [
    {
        key: 'status',
        label: t('common.all_statuses'),
        options: ['pending', 'approved', 'rejected'].map((status) => ({ value: status, label: statusLabel(status) })),
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
        approved: 'bg-emerald-50 text-emerald-700',
        pending: 'bg-amber-50 text-amber-700',
        rejected: 'bg-red-50 text-red-700',
    }[status] || 'bg-slate-50 text-slate-600';
}

function approveTransfer(transfer) {
    router.patch(`/manage/bank-transfers/${transfer.id}/approve`, {}, { preserveScroll: true });
}

function rejectTransfer(transfer) {
    router.patch(`/manage/bank-transfers/${transfer.id}/reject`, {}, { preserveScroll: true });
}
</script>

<template>
    <Head :title="t('transfers.title')" />

    <AdminLayout>
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-slate-950">{{ t('transfers.title') }}</h1>
        </div>

        <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center gap-3 border-b border-slate-200 px-5 py-4">
                <div class="grid h-10 w-10 place-items-center rounded-lg bg-amber-50 text-amber-700">
                    <Landmark class="h-5 w-5" />
                </div>
                <div>
                    <h2 class="font-semibold text-slate-950">{{ t('transfers.queue') }}</h2>
                    <p class="text-xs text-slate-500">{{ t('transfers.count', { count: transfersTotal }) }}</p>
                </div>
            </div>

            <IndexFilters action="/manage/bank-transfers" :filters="filters" :fields="filterFields" />

            <div class="overflow-x-auto lg:hidden">
                <table class="min-w-[820px] w-full text-start text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-3 text-start">{{ t('common.reference') }}</th>
                            <th class="px-4 py-3 text-start">{{ t('common.customer') }}</th>
                            <th class="px-4 py-3 text-start">{{ t('common.order') }}</th>
                            <th class="px-4 py-3 text-start">{{ t('common.amount') }}</th>
                            <th class="px-4 py-3 text-start">{{ t('common.status') }}</th>
                            <th class="px-4 py-3 text-end">{{ t('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="transfer in transferItems" :key="`mobile-${transfer.id}`" class="hover:bg-slate-50/70">
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-800">{{ transfer.reference_number || t('common.no_reference') }}</td>
                            <td class="max-w-[220px] px-4 py-3">
                                <p class="break-words font-medium text-slate-800">{{ transfer.customer }}</p>
                                <p class="break-words text-xs text-slate-500">{{ transfer.email }}</p>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-700">{{ transfer.order_number }}</td>
                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-900">{{ transfer.amount }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-md px-2 py-1 text-xs font-medium" :class="statusClass(transfer.status)">{{ statusLabel(transfer.status) }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <Link class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 text-slate-600 hover:bg-slate-50" :href="`/manage/bank-transfers/${transfer.id}`" :title="t('common.view')">
                                        <Eye class="h-4 w-4" />
                                    </Link>
                                    <button class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-emerald-200 text-emerald-700 hover:bg-emerald-50 disabled:opacity-50" type="button" :disabled="transfer.status !== 'pending'" :title="t('transfers.approve')" @click="approveTransfer(transfer)">
                                        <Check class="h-4 w-4" />
                                    </button>
                                    <button class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-red-200 text-red-600 hover:bg-red-50 disabled:opacity-50" type="button" :disabled="transfer.status !== 'pending'" :title="t('transfers.reject')" @click="rejectTransfer(transfer)">
                                        <X class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="hidden divide-y divide-slate-100 lg:block">
                <article v-for="transfer in transferItems" :key="transfer.id" class="grid gap-4 px-5 py-4 lg:grid-cols-[1fr_auto]">
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                        <div>
                            <p class="text-xs text-slate-500">{{ t('common.reference') }}</p>
                            <p class="font-medium text-slate-800">{{ transfer.reference_number || t('common.no_reference') }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">{{ t('common.customer') }}</p>
                            <p class="font-medium text-slate-800">{{ transfer.customer }}</p>
                            <p class="text-xs text-slate-500">{{ transfer.email }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">{{ t('common.order') }}</p>
                            <p class="font-medium text-slate-800">{{ transfer.order_number }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">{{ t('common.amount') }}</p>
                            <p class="font-medium text-slate-800">{{ transfer.amount }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">{{ t('common.status') }}</p>
                            <span class="mt-1 inline-flex rounded-md px-2 py-1 text-xs font-medium" :class="statusClass(transfer.status)">{{ statusLabel(transfer.status) }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <Link class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" :href="`/manage/bank-transfers/${transfer.id}`">
                            <Eye class="h-4 w-4" />
                            {{ t('common.view') }}
                        </Link>
                        <button
                            class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 px-3 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-50 disabled:opacity-50"
                            type="button"
                            :disabled="transfer.status !== 'pending'"
                            @click="approveTransfer(transfer)"
                        >
                            <Check class="h-4 w-4" />
                            {{ t('transfers.approve') }}
                        </button>
                        <button
                            class="inline-flex items-center gap-2 rounded-lg border border-red-200 px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50 disabled:opacity-50"
                            type="button"
                            :disabled="transfer.status !== 'pending'"
                            @click="rejectTransfer(transfer)"
                        >
                            <X class="h-4 w-4" />
                            {{ t('transfers.reject') }}
                        </button>
                    </div>
                </article>

                <div v-if="transferItems.length === 0" class="px-5 py-12 text-center text-sm text-slate-500">
                    {{ t('transfers.empty') }}
                </div>
            </div>

            <div v-if="transferItems.length === 0" class="px-5 py-12 text-center text-sm text-slate-500 lg:hidden">
                {{ t('transfers.empty') }}
            </div>

            <Pagination :paginator="transfers" />
        </section>
    </AdminLayout>
</template>
