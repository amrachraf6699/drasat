<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import IndexFilters from '@/Components/Admin/IndexFilters.vue';
import Modal from '@/Components/Admin/Modal.vue';
import Pagination from '@/Components/Admin/Pagination.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { Edit3, Eye, Save, ShieldCheck, Trash2, UserPlus, X } from 'lucide-vue-next';

const props = defineProps({
    admins: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    filterOptions: { type: Object, default: () => ({ roles: [] }) },
});

const { statusLabel, t } = useTranslations();
const adminItems = computed(() => props.admins.data || []);
const adminsTotal = computed(() => props.admins.meta?.total ?? props.admins.total ?? adminItems.value.length);
const modalOpen = ref(false);

const filterFields = computed(() => [
    {
        key: 'status',
        label: t('common.all_statuses'),
        options: ['active', 'inactive'].map((status) => ({ value: status, label: statusLabel(status) })),
    },
    {
        key: 'role',
        label: t('common.all_roles'),
        options: (props.filterOptions.roles || []).map((role) => ({ value: role, label: role })),
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

const blank = {
    id: null,
    name: '',
    email: '',
    password: '',
    is_active: true,
    roles: [],
};

const form = useForm({ ...blank });

function resetForm() {
    Object.assign(form, { ...blank, roles: [] });
    form.clearErrors();
}

function openCreateModal() {
    resetForm();
    modalOpen.value = true;
}

function editAdmin(admin) {
    Object.assign(form, {
        ...blank,
        ...admin,
        password: '',
        roles: [...(admin.roles || [])],
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
        form.put(`/manage/admins/${form.id}`, { preserveScroll: true, onSuccess: () => closeModal() });
        return;
    }

    form.post('/manage/admins', { preserveScroll: true, onSuccess: () => closeModal() });
}

function destroyAdmin(admin) {
    if (confirm(t('admins.delete_confirm', { name: admin.name }))) {
        router.delete(`/manage/admins/${admin.id}`, { preserveScroll: true });
    }
}

function statusClass(active) {
    return active
        ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100'
        : 'bg-slate-100 text-slate-600 ring-1 ring-slate-200';
}
</script>

<template>
    <Head :title="t('admins.title')" />

    <AdminLayout>
        <div class="mb-6 flex min-w-0 flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <h1 class="text-2xl font-semibold text-slate-950">{{ t('admins.title') }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ t('admins.count', { count: adminsTotal }) }}</p>
            </div>
            <button class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700" type="button" @click="openCreateModal">
                <UserPlus class="h-4 w-4" />
                {{ t('admins.new') }}
            </button>
        </div>

        <section class="min-w-0 rounded-lg border border-slate-200 bg-white shadow-sm">
            <IndexFilters action="/manage/admins" :filters="filters" :fields="filterFields" />

            <div class="overflow-x-auto lg:hidden">
                <table class="min-w-[720px] w-full text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-3 text-start">{{ t('common.name') }}</th>
                            <th class="px-4 py-3 text-start">{{ t('common.email') }}</th>
                            <th class="px-4 py-3 text-start">{{ t('common.roles') }}</th>
                            <th class="px-4 py-3 text-center">{{ t('common.status') }}</th>
                            <th class="px-4 py-3 text-end">{{ t('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="admin in adminItems" :key="`mobile-${admin.id}`" class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 font-semibold text-slate-900">{{ admin.name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ admin.email }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ admin.roles.join(', ') || '-' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="rounded-md px-2 py-1 text-xs font-semibold" :class="statusClass(admin.is_active)">
                                    {{ admin.is_active ? statusLabel('active') : statusLabel('inactive') }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <Link class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 text-slate-600 hover:bg-slate-50" :href="`/manage/admins/${admin.id}`" :title="t('common.view')">
                                        <Eye class="h-4 w-4" />
                                    </Link>
                                    <button class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 text-slate-600 hover:bg-slate-50" type="button" :title="t('common.edit')" @click="editAdmin(admin)">
                                        <Edit3 class="h-4 w-4" />
                                    </button>
                                    <button class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-red-200 text-red-600 hover:bg-red-50" type="button" :title="t('common.delete')" @click="destroyAdmin(admin)">
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="hidden divide-y divide-slate-100 lg:block">
                <article v-for="admin in adminItems" :key="admin.id" class="min-w-0 px-5 py-4 transition hover:bg-slate-50/70">
                    <div class="grid min-w-0 gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(160px,240px)_140px_auto] lg:items-center">
                        <div class="flex min-w-0 items-start gap-3">
                            <div class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-emerald-50 text-emerald-700">
                                <ShieldCheck class="h-5 w-5" />
                            </div>
                            <div class="min-w-0">
                                <p class="break-words font-semibold text-slate-900">{{ admin.name }}</p>
                                <p class="break-words text-sm text-slate-500">{{ admin.email }}</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <span v-for="role in admin.roles" :key="role" class="rounded-md bg-slate-50 px-2 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">{{ role }}</span>
                            <span v-if="admin.roles.length === 0" class="text-sm text-slate-500">-</span>
                        </div>

                        <div>
                            <span class="rounded-md px-2 py-1 text-xs font-semibold" :class="statusClass(admin.is_active)">
                                {{ admin.is_active ? statusLabel('active') : statusLabel('inactive') }}
                            </span>
                        </div>

                        <div class="flex justify-start gap-2 lg:justify-end">
                            <Link class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 text-slate-600 hover:bg-slate-50" :href="`/manage/admins/${admin.id}`" :title="t('common.view')">
                                <Eye class="h-4 w-4" />
                            </Link>
                            <button class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 text-slate-600 hover:bg-slate-50" type="button" :title="t('common.edit')" @click="editAdmin(admin)">
                                <Edit3 class="h-4 w-4" />
                            </button>
                            <button class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-red-200 text-red-600 hover:bg-red-50" type="button" :title="t('common.delete')" @click="destroyAdmin(admin)">
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </article>

                <div v-if="adminItems.length === 0" class="px-5 py-12 text-center text-sm text-slate-500">
                    {{ t('admins.empty') }}
                </div>
            </div>

            <div v-if="adminItems.length === 0" class="px-5 py-12 text-center text-sm text-slate-500 lg:hidden">
                {{ t('admins.empty') }}
            </div>

            <Pagination :paginator="admins" />
        </section>

        <Modal
            :show="modalOpen"
            :title="form.id ? t('admins.edit') : t('admins.create')"
            :description="t('admins.modal_description')"
            width="max-w-2xl"
            @close="closeModal"
        >
            <form class="grid gap-5" @submit.prevent="submit">
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="block">
                        <span class="mb-1 block text-xs font-semibold text-slate-600">{{ t('common.name') }}</span>
                        <input v-model="form.name" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-500" type="text">
                        <span v-if="form.errors.name" class="mt-1 block text-xs text-red-600">{{ form.errors.name }}</span>
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-semibold text-slate-600">{{ t('common.email') }}</span>
                        <input v-model="form.email" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-500" type="email">
                        <span v-if="form.errors.email" class="mt-1 block text-xs text-red-600">{{ form.errors.email }}</span>
                    </label>
                </div>

                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-slate-600">{{ form.id ? t('admins.new_password_optional') : t('common.password') }}</span>
                    <input v-model="form.password" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-500" type="password">
                    <span v-if="form.errors.password" class="mt-1 block text-xs text-red-600">{{ form.errors.password }}</span>
                </label>

                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input v-model="form.is_active" class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" type="checkbox">
                    {{ t('admins.active_account') }}
                </label>

                <div>
                    <p class="mb-2 text-xs font-semibold text-slate-600">{{ t('common.roles') }}</p>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <label v-for="role in filterOptions.roles" :key="role" class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700">
                            <input v-model="form.roles" class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" type="checkbox" :value="role">
                            {{ role }}
                        </label>
                    </div>
                    <span v-if="form.errors.roles" class="mt-1 block text-xs text-red-600">{{ form.errors.roles }}</span>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:justify-end">
                    <button type="button" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50" @click="closeModal">
                        <X class="h-4 w-4" />
                        {{ t('common.cancel') }}
                    </button>
                    <button class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60" :disabled="form.processing">
                        <Save class="h-4 w-4" />
                        {{ form.id ? t('common.save_changes') : t('admins.create') }}
                    </button>
                </div>
            </form>
        </Modal>
    </AdminLayout>
</template>
