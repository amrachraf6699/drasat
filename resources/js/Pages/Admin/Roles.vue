<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import IndexFilters from '@/Components/Admin/IndexFilters.vue';
import Modal from '@/Components/Admin/Modal.vue';
import Pagination from '@/Components/Admin/Pagination.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { Edit3, Eye, KeyRound, Save, ShieldCheck, Trash2, X } from 'lucide-vue-next';

const props = defineProps({
    roles: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    filterOptions: { type: Object, default: () => ({ permissions: [] }) },
});

const { t } = useTranslations();
const roleItems = computed(() => props.roles.data || []);
const rolesTotal = computed(() => props.roles.meta?.total ?? props.roles.total ?? roleItems.value.length);
const modalOpen = ref(false);

const filterFields = computed(() => [
    {
        key: 'permission',
        label: t('roles.all_permissions'),
        options: (props.filterOptions.permissions || []).map((permission) => ({ value: permission, label: permission })),
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

const permissionGroups = computed(() => {
    return (props.filterOptions.permissions || []).reduce((groups, permission) => {
        const [group] = permission.split('.');
        groups[group] = groups[group] || [];
        groups[group].push(permission);
        return groups;
    }, {});
});

const blank = {
    id: null,
    name: '',
    permissions: [],
};

const form = useForm({ ...blank });

function resetForm() {
    Object.assign(form, { ...blank, permissions: [] });
    form.clearErrors();
}

function openCreateModal() {
    resetForm();
    modalOpen.value = true;
}

function editRole(role) {
    Object.assign(form, {
        ...blank,
        ...role,
        permissions: [...(role.permissions || [])],
    });
    form.clearErrors();
    modalOpen.value = true;
}

function closeModal() {
    modalOpen.value = false;
    resetForm();
}

function submit() {
    if (form.id) {
        form.put(`/manage/roles/${form.id}`, { preserveScroll: true, onSuccess: () => closeModal() });
        return;
    }

    form.post('/manage/roles', { preserveScroll: true, onSuccess: () => closeModal() });
}

function destroyRole(role) {
    if (confirm(t('roles.delete_confirm', { name: role.name }))) {
        router.delete(`/manage/roles/${role.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head :title="t('roles.title')" />

    <AdminLayout>
        <div class="mb-6 flex min-w-0 flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <h1 class="text-2xl font-semibold text-slate-950">{{ t('roles.title') }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ t('roles.count', { count: rolesTotal }) }}</p>
            </div>
            <button class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700" type="button" @click="openCreateModal">
                <KeyRound class="h-4 w-4" />
                {{ t('roles.new') }}
            </button>
        </div>

        <section class="min-w-0 rounded-lg border border-slate-200 bg-white shadow-sm">
            <IndexFilters action="/manage/roles" :filters="filters" :fields="filterFields" />

            <div class="overflow-x-auto lg:hidden">
                <table class="min-w-[720px] w-full text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-3 text-start">{{ t('common.name') }}</th>
                            <th class="px-4 py-3 text-center">{{ t('common.permissions') }}</th>
                            <th class="px-4 py-3 text-center">{{ t('roles.assigned_admins') }}</th>
                            <th class="px-4 py-3 text-end">{{ t('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="role in roleItems" :key="`mobile-${role.id}`" class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 font-semibold text-slate-900">{{ role.name }}</td>
                            <td class="px-4 py-3 text-center text-slate-600">{{ role.permissions_count }}</td>
                            <td class="px-4 py-3 text-center text-slate-600">{{ role.admins_count }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <Link class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 text-slate-600 hover:bg-slate-50" :href="`/manage/roles/${role.id}`" :title="t('common.view')">
                                        <Eye class="h-4 w-4" />
                                    </Link>
                                    <button class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 text-slate-600 hover:bg-slate-50" type="button" :title="t('common.edit')" @click="editRole(role)">
                                        <Edit3 class="h-4 w-4" />
                                    </button>
                                    <button class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-red-200 text-red-600 hover:bg-red-50" type="button" :title="t('common.delete')" @click="destroyRole(role)">
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="hidden divide-y divide-slate-100 lg:block">
                <article v-for="role in roleItems" :key="role.id" class="min-w-0 px-5 py-4 transition hover:bg-slate-50/70">
                    <div class="grid min-w-0 gap-4 lg:grid-cols-[minmax(0,1fr)_140px_140px_auto] lg:items-center">
                        <div class="flex min-w-0 items-start gap-3">
                            <div class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-emerald-50 text-emerald-700">
                                <ShieldCheck class="h-5 w-5" />
                            </div>
                            <div class="min-w-0">
                                <p class="break-words font-semibold text-slate-900">{{ role.name }}</p>
                                <p class="mt-1 line-clamp-2 break-words text-sm text-slate-500">{{ role.permissions.join(', ') || '-' }}</p>
                            </div>
                        </div>

                        <div>
                            <p class="text-xs font-semibold uppercase text-slate-400">{{ t('common.permissions') }}</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ role.permissions_count }}</p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold uppercase text-slate-400">{{ t('roles.assigned_admins') }}</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ role.admins_count }}</p>
                        </div>

                        <div class="flex justify-start gap-2 lg:justify-end">
                            <Link class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 text-slate-600 hover:bg-slate-50" :href="`/manage/roles/${role.id}`" :title="t('common.view')">
                                <Eye class="h-4 w-4" />
                            </Link>
                            <button class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 text-slate-600 hover:bg-slate-50" type="button" :title="t('common.edit')" @click="editRole(role)">
                                <Edit3 class="h-4 w-4" />
                            </button>
                            <button class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-red-200 text-red-600 hover:bg-red-50" type="button" :title="t('common.delete')" @click="destroyRole(role)">
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </article>

                <div v-if="roleItems.length === 0" class="px-5 py-12 text-center text-sm text-slate-500">
                    {{ t('roles.empty') }}
                </div>
            </div>

            <div v-if="roleItems.length === 0" class="px-5 py-12 text-center text-sm text-slate-500 lg:hidden">
                {{ t('roles.empty') }}
            </div>

            <Pagination :paginator="roles" />
        </section>

        <Modal
            :show="modalOpen"
            :title="form.id ? t('roles.edit') : t('roles.create')"
            :description="t('roles.modal_description')"
            width="max-w-3xl"
            @close="closeModal"
        >
            <form class="grid gap-5" @submit.prevent="submit">
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-slate-600">{{ t('common.name') }}</span>
                    <input v-model="form.name" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-500" type="text">
                    <span v-if="form.errors.name" class="mt-1 block text-xs text-red-600">{{ form.errors.name }}</span>
                </label>

                <div>
                    <p class="mb-2 text-xs font-semibold text-slate-600">{{ t('common.permissions') }}</p>
                    <div class="grid gap-4 md:grid-cols-2">
                        <section v-for="(permissions, group) in permissionGroups" :key="group" class="rounded-lg border border-slate-200 p-3">
                            <p class="mb-2 text-xs font-semibold uppercase text-slate-400">{{ group }}</p>
                            <div class="grid gap-2">
                                <label v-for="permission in permissions" :key="permission" class="flex items-center gap-2 text-sm text-slate-700">
                                    <input v-model="form.permissions" class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" type="checkbox" :value="permission">
                                    {{ permission }}
                                </label>
                            </div>
                        </section>
                    </div>
                    <span v-if="form.errors.permissions" class="mt-1 block text-xs text-red-600">{{ form.errors.permissions }}</span>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:justify-end">
                    <button type="button" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50" @click="closeModal">
                        <X class="h-4 w-4" />
                        {{ t('common.cancel') }}
                    </button>
                    <button class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60" :disabled="form.processing">
                        <Save class="h-4 w-4" />
                        {{ form.id ? t('common.save_changes') : t('roles.create') }}
                    </button>
                </div>
            </form>
        </Modal>
    </AdminLayout>
</template>
