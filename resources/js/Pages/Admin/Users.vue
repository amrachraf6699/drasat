<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import IndexFilters from '@/Components/Admin/IndexFilters.vue';
import Pagination from '@/Components/Admin/Pagination.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { CalendarDays, Eye, ShoppingBag, UsersRound } from 'lucide-vue-next';

const props = defineProps({
    users: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const { t } = useTranslations();
const userItems = computed(() => props.users.data || []);
const usersTotal = computed(() => props.users.meta?.total ?? props.users.total ?? userItems.value.length);
const filterFields = computed(() => [
    {
        key: 'purchases',
        label: t('users.purchases'),
        options: [
            { value: 'with', label: t('common.with_related') },
            { value: 'without', label: t('common.without_related') },
        ],
    },
    {
        key: 'sort',
        label: t('common.sort_by'),
        options: [
            { value: 'newest', label: t('common.newest') },
            { value: 'oldest', label: t('common.oldest') },
            { value: 'name_az', label: t('common.name_az') },
            { value: 'name_za', label: t('common.name_za') },
        ],
    },
]);
</script>

<template>
    <Head :title="t('users.title')" />

    <AdminLayout>
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-slate-950">{{ t('users.title') }}</h1>
        </div>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <IndexFilters action="/manage/users" :filters="filters" :fields="filterFields" />

            <div class="overflow-x-auto lg:hidden">
                <table class="min-w-[620px] w-full text-start text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-3 text-start">{{ t('common.name') }}</th>
                            <th class="px-4 py-3 text-start">{{ t('common.email') }}</th>
                            <th class="px-4 py-3 text-start">{{ t('users.purchases') }}</th>
                            <th class="px-4 py-3 text-start">{{ t('users.joined') }}</th>
                            <th class="px-4 py-3 text-end">{{ t('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="user in userItems" :key="`mobile-${user.id}`" class="hover:bg-slate-50/70">
                            <td class="max-w-[180px] px-4 py-3 font-semibold text-slate-900">{{ user.name }}</td>
                            <td class="max-w-[220px] px-4 py-3">
                                <p class="truncate text-slate-600">{{ user.email }}</p>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-900">{{ user.purchases_count }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-700">{{ user.joined_at }}</td>
                            <td class="px-4 py-3 text-end">
                                <Link class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 text-slate-600 hover:bg-slate-50" :href="`/manage/users/${user.id}`" :title="t('common.view')">
                                    <Eye class="h-4 w-4" />
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="hidden divide-y divide-slate-100 lg:block">
                <article v-for="user in userItems" :key="user.id" class="min-w-0 px-5 py-4 transition hover:bg-slate-50/70">
                    <div class="grid min-w-0 gap-4 md:grid-cols-[minmax(0,1fr)_130px_160px_auto] md:items-center">
                        <div class="flex min-w-0 items-start gap-3">
                            <div class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-teal-50 text-sm font-semibold text-teal-700">
                                {{ (user.name || 'U').charAt(0) }}
                            </div>
                            <div class="min-w-0">
                                <p class="break-words font-semibold text-slate-900">{{ user.name }}</p>
                                <p class="break-words text-sm text-slate-500">{{ user.email }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <ShoppingBag class="h-4 w-4 text-slate-400" />
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ user.purchases_count }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <CalendarDays class="h-4 w-4 text-slate-400" />
                            <div>
                                <p class="text-sm font-medium text-slate-800">{{ user.joined_at }}</p>
                                <p class="text-xs text-slate-500">{{ t('users.joined') }}</p>
                            </div>
                        </div>

                        <div class="flex justify-start md:justify-end">
                            <Link class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 text-slate-600 hover:bg-slate-50" :href="`/manage/users/${user.id}`" :title="t('common.view')">
                                <Eye class="h-4 w-4" />
                            </Link>
                        </div>
                    </div>
                </article>

                <div v-if="userItems.length === 0" class="px-5 py-12 text-center text-sm text-slate-500">
                    {{ t('users.empty') }}
                </div>
            </div>

            <div v-if="userItems.length === 0" class="px-5 py-12 text-center text-sm text-slate-500 lg:hidden">
                {{ t('users.empty') }}
            </div>

            <Pagination :paginator="users" />
        </section>
    </AdminLayout>
</template>
