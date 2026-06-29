<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { ArrowLeft, ExternalLink } from 'lucide-vue-next';

defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    backHref: { type: String, required: true },
    stats: { type: Array, default: () => [] },
    fields: { type: Array, default: () => [] },
    sections: { type: Array, default: () => [] },
});

const { t } = useTranslations();
</script>

<template>
    <Head :title="title" />

    <AdminLayout>
        <div class="mb-6 flex min-w-0 flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <Link :href="backHref" class="mb-3 inline-flex items-center gap-2 text-sm font-semibold text-emerald-700 hover:text-emerald-800">
                    <ArrowLeft class="h-4 w-4" />
                    {{ t('common.back') }}
                </Link>
                <h1 class="break-words text-2xl font-semibold text-slate-950">{{ title }}</h1>
                <p v-if="subtitle" class="mt-2 break-words text-sm text-slate-500">{{ subtitle }}</p>
            </div>
        </div>

        <div v-if="stats.length" class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div v-for="stat in stats" :key="stat.label" class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase text-slate-500">{{ stat.label }}</p>
                <p class="mt-2 break-words text-xl font-bold text-slate-950">{{ stat.value }}</p>
            </div>
        </div>

        <section class="mb-6 rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="font-semibold text-slate-950">{{ t('common.overview') }}</h2>
            </div>
            <dl class="grid gap-0 divide-y divide-slate-100 md:grid-cols-2 md:divide-x md:divide-y-0">
                <div v-for="field in fields" :key="field.label" class="min-w-0 px-5 py-4">
                    <dt class="text-xs font-semibold uppercase text-slate-500">{{ field.label }}</dt>
                    <dd class="mt-2 whitespace-pre-wrap break-words text-sm font-medium text-slate-900">{{ field.value || '-' }}</dd>
                </div>
            </dl>
        </section>

        <section v-for="section in sections" :key="section.title" class="mb-6 rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-1 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="font-semibold text-slate-950">{{ section.title }}</h2>
                    <p v-if="section.subtitle" class="text-xs text-slate-500">{{ section.subtitle }}</p>
                </div>
            </div>

            <div v-if="section.rows?.length" class="overflow-x-auto">
                <table class="min-w-full text-start text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500">
                        <tr>
                            <th v-for="column in section.columns" :key="column.key" class="whitespace-nowrap px-4 py-3 text-start">
                                {{ column.label }}
                            </th>
                            <th v-if="section.showLinks" class="px-4 py-3 text-end">{{ t('common.view') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="row in section.rows" :key="row.id || JSON.stringify(row)" class="hover:bg-slate-50/70">
                            <td v-for="column in section.columns" :key="column.key" class="max-w-[280px] px-4 py-3">
                                <span class="break-words text-slate-700">{{ row[column.key] || '-' }}</span>
                            </td>
                            <td v-if="section.showLinks" class="px-4 py-3 text-end">
                                <Link v-if="row.href" :href="row.href" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 text-slate-600 hover:bg-slate-50">
                                    <ExternalLink class="h-4 w-4" />
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="px-5 py-10 text-center text-sm text-slate-500">
                {{ section.empty || t('common.no_related_records') }}
            </div>
        </section>
    </AdminLayout>
</template>
