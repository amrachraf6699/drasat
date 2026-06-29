<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Modal from '@/Components/Admin/Modal.vue';
import IndexFilters from '@/Components/Admin/IndexFilters.vue';
import Pagination from '@/Components/Admin/Pagination.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { Edit3, Eye, PlusCircle, Save, SlidersHorizontal, Trash2, X } from 'lucide-vue-next';

const props = defineProps({
    settings: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    filterOptions: { type: Object, default: () => ({ groups: [] }) },
});

const { inputTypeLabel, locale, t } = useTranslations();
const modalOpen = ref(false);
const settingItems = computed(() => props.settings.data || []);
const filterFields = computed(() => [
    {
        key: 'group',
        label: t('common.all_groups'),
        options: (props.filterOptions.groups || []).map((group) => ({ value: group, label: group })),
    },
    {
        key: 'input_type',
        label: t('common.all_types'),
        options: ['text', 'textarea', 'image', 'url', 'email', 'number', 'boolean'].map((type) => ({ value: type, label: inputTypeLabel(type) })),
    },
    {
        key: 'translatable',
        label: t('common.all_values'),
        options: [
            { value: 'yes', label: t('common.translated') },
            { value: 'no', label: t('common.single_value') },
        ],
    },
    {
        key: 'sort',
        label: t('common.sort_by'),
        options: [
            { value: 'key', label: t('common.key') },
            { value: 'newest', label: t('common.newest') },
            { value: 'oldest', label: t('common.oldest') },
        ],
    },
]);

const blank = {
    id: null,
    group: 'general',
    input_type: 'text',
    key: '',
    value: '',
    value_en: '',
    value_ar: '',
    is_translatable: false,
};

const form = useForm({ ...blank });

function resetForm() {
    Object.assign(form, { ...blank });
    form.clearErrors();
}

function openCreateModal() {
    resetForm();
    modalOpen.value = true;
}

function editSetting(setting) {
    Object.assign(form, { ...blank, ...setting });
    form.clearErrors();
    modalOpen.value = true;
}

function closeModal() {
    modalOpen.value = false;
    resetForm();
}

function submit() {
    if (form.id) {
        form.put(`/manage/settings/${form.id}`, { preserveScroll: true, onSuccess: () => closeModal() });
        return;
    }

    form.post('/manage/settings', { preserveScroll: true, onSuccess: () => closeModal() });
}

function destroySetting(setting) {
    if (confirm(t('settings.delete_confirm', { key: `${setting.group}.${setting.key}` }))) {
        router.delete(`/manage/settings/${setting.id}`, { preserveScroll: true });
    }
}

function settingValue(setting) {
    if (setting.is_translatable) {
        const value = locale.value === 'ar' ? (setting.value_ar || setting.value_en) : (setting.value_en || setting.value_ar);

        return value || t('common.empty_translated_value');
    }

    return setting.value || t('common.empty_value');
}
</script>

<template>
    <Head :title="t('settings.title')" />

    <AdminLayout>
        <div class="mb-6 flex min-w-0 flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <h1 class="text-2xl font-semibold text-slate-950">{{ t('settings.title') }}</h1>
            </div>
            <button class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700" type="button" @click="openCreateModal">
                <PlusCircle class="h-4 w-4" />
                {{ t('settings.new') }}
            </button>
        </div>

        <section class="min-w-0 rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="font-semibold text-slate-950">{{ t('settings.list') }}</h2>
            </div>

            <IndexFilters action="/manage/settings" :filters="filters" :fields="filterFields" />

            <div class="overflow-x-auto lg:hidden">
                <table class="min-w-[700px] w-full text-start text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-3 text-start">{{ t('common.key') }}</th>
                            <th class="px-4 py-3 text-start">{{ t('common.group') }}</th>
                            <th class="px-4 py-3 text-start">{{ t('common.input_type') }}</th>
                            <th class="px-4 py-3 text-start">{{ t('common.value') }}</th>
                            <th class="px-4 py-3 text-end">{{ t('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="setting in settingItems" :key="`mobile-${setting.id}`" class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 font-semibold text-slate-900">{{ setting.key }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-700">{{ setting.group }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-700">{{ inputTypeLabel(setting.input_type) }}</td>
                            <td class="max-w-[260px] px-4 py-3">
                                <p class="line-clamp-2 break-words text-slate-600">{{ settingValue(setting) }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <Link class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 text-slate-600 hover:bg-slate-50" :href="`/manage/settings/${setting.id}`" :title="t('common.view')">
                                        <Eye class="h-4 w-4" />
                                    </Link>
                                    <button class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 text-slate-600 hover:bg-slate-50" type="button" :title="t('common.edit')" @click="editSetting(setting)">
                                        <Edit3 class="h-4 w-4" />
                                    </button>
                                    <button class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-red-200 text-red-600 hover:bg-red-50" type="button" :title="t('common.delete')" @click="destroySetting(setting)">
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="hidden divide-y divide-slate-100 lg:block">
                <article v-for="setting in settingItems" :key="setting.id" class="min-w-0 px-5 py-4 transition hover:bg-slate-50/70">
                    <div class="grid min-w-0 gap-4 lg:grid-cols-[minmax(0,1fr)_150px_130px_auto] lg:items-center">
                        <div class="flex min-w-0 items-start gap-3">
                            <div class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-slate-100 text-slate-700">
                                <SlidersHorizontal class="h-5 w-5" />
                            </div>
                            <div class="min-w-0">
                                <div class="flex min-w-0 flex-wrap items-center gap-2">
                                    <p class="break-words font-semibold text-slate-900">{{ setting.key }}</p>
                                    <span class="rounded-md bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">{{ setting.group }}</span>
                                </div>
                                <p class="mt-2 break-words text-sm leading-6 text-slate-500">{{ settingValue(setting) }}</p>
                            </div>
                        </div>

                        <div>
                            <p class="text-sm font-medium capitalize text-slate-800">{{ inputTypeLabel(setting.input_type) }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ t('common.input_type') }}</p>
                        </div>

                        <div>
                            <span class="inline-flex rounded-md px-2 py-1 text-xs font-semibold" :class="setting.is_translatable ? 'bg-blue-50 text-blue-700 ring-1 ring-blue-100' : 'bg-slate-100 text-slate-600 ring-1 ring-slate-200'">
                                {{ setting.is_translatable ? t('common.translated') : t('common.single_value') }}
                            </span>
                        </div>

                        <div class="flex justify-start gap-2 lg:justify-end">
                            <Link class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 text-slate-600 hover:bg-slate-50" :href="`/manage/settings/${setting.id}`" :title="t('common.view')">
                                <Eye class="h-4 w-4" />
                            </Link>
                            <button class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 text-slate-600 hover:bg-slate-50" type="button" :title="t('common.edit')" @click="editSetting(setting)">
                                <Edit3 class="h-4 w-4" />
                            </button>
                            <button class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-red-200 text-red-600 hover:bg-red-50" type="button" :title="t('common.delete')" @click="destroySetting(setting)">
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </article>

                <div v-if="settingItems.length === 0" class="px-5 py-12 text-center text-sm text-slate-500">
                    {{ t('settings.empty') }}
                </div>
            </div>

            <div v-if="settingItems.length === 0" class="px-5 py-12 text-center text-sm text-slate-500 lg:hidden">
                {{ t('settings.empty') }}
            </div>

            <Pagination :paginator="settings" />
        </section>

        <Modal
            :show="modalOpen"
            :title="form.id ? t('settings.edit') : t('settings.create')"
            :description="t('settings.modal_description')"
            width="max-w-3xl"
            @close="closeModal"
        >
            <form class="grid gap-5" @submit.prevent="submit">
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="block">
                        <span class="mb-1 block text-xs font-semibold text-slate-600">{{ t('common.group') }}</span>
                        <input v-model="form.group" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-500" type="text">
                        <span v-if="form.errors.group" class="mt-1 block text-xs text-red-600">{{ form.errors.group }}</span>
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-semibold text-slate-600">{{ t('common.input_type') }}</span>
                        <select v-model="form.input_type" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-500">
                            <option value="text">{{ inputTypeLabel('text') }}</option>
                            <option value="textarea">{{ inputTypeLabel('textarea') }}</option>
                            <option value="image">{{ inputTypeLabel('image') }}</option>
                            <option value="url">{{ inputTypeLabel('url') }}</option>
                            <option value="email">{{ inputTypeLabel('email') }}</option>
                            <option value="number">{{ inputTypeLabel('number') }}</option>
                            <option value="boolean">{{ inputTypeLabel('boolean') }}</option>
                        </select>
                    </label>
                </div>

                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-slate-600">{{ t('common.key') }}</span>
                    <input v-model="form.key" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-500" type="text">
                    <span v-if="form.errors.key" class="mt-1 block text-xs text-red-600">{{ form.errors.key }}</span>
                </label>

                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input v-model="form.is_translatable" class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" type="checkbox">
                    {{ t('common.store_translated_values') }}
                </label>

                <label v-if="!form.is_translatable" class="block">
                    <span class="mb-1 block text-xs font-semibold text-slate-600">{{ t('common.value') }}</span>
                    <textarea v-model="form.value" class="min-h-24 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-500" />
                </label>

                <div v-else class="grid gap-4 md:grid-cols-2">
                    <label class="block">
                        <span class="mb-1 block text-xs font-semibold text-slate-600">{{ t('common.value_en') }}</span>
                        <textarea v-model="form.value_en" class="min-h-24 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-500" />
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-semibold text-slate-600">{{ t('common.value_ar') }}</span>
                        <textarea v-model="form.value_ar" dir="rtl" class="min-h-24 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-500" />
                    </label>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:justify-end">
                    <button type="button" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50" @click="closeModal">
                        <X class="h-4 w-4" />
                        {{ t('common.cancel') }}
                    </button>
                    <button class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60" :disabled="form.processing">
                        <Save class="h-4 w-4" />
                        {{ form.id ? t('common.save_changes') : t('settings.create') }}
                    </button>
                </div>
            </form>
        </Modal>
    </AdminLayout>
</template>
