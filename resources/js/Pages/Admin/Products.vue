<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Modal from '@/Components/Admin/Modal.vue';
import IndexFilters from '@/Components/Admin/IndexFilters.vue';
import Pagination from '@/Components/Admin/Pagination.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { Edit3, Eye, FileText, Image, PackagePlus, Save, Trash2, X } from 'lucide-vue-next';

const props = defineProps({
    products: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const { locale, statusLabel, t } = useTranslations();
const modalOpen = ref(false);
const productItems = computed(() => props.products.data || []);
const productsTotal = computed(() => props.products.meta?.total ?? props.products.total ?? productItems.value.length);
const filterFields = computed(() => [
    {
        key: 'status',
        label: t('common.all_statuses'),
        options: ['active', 'inactive', 'draft'].map((status) => ({ value: status, label: statusLabel(status) })),
    },
    {
        key: 'documents',
        label: t('common.documents'),
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
            { value: 'price_high', label: t('common.price_high') },
            { value: 'price_low', label: t('common.price_low') },
        ],
    },
]);

const blank = {
    id: null,
    sku: '',
    title_en: '',
    title_ar: '',
    short_description_en: '',
    short_description_ar: '',
    description_en: '',
    description_ar: '',
    price: 0,
    currency: 'EGP',
    status: 'draft',
    cover: null,
    documents: [],
    _method: 'post',
};

const form = useForm({ ...blank });

function resetForm() {
    Object.assign(form, { ...blank, documents: [] });
    form.clearErrors();
}

function openCreateModal() {
    resetForm();
    modalOpen.value = true;
}

function editProduct(product) {
    Object.assign(form, {
        ...blank,
        ...product,
        cover: null,
        documents: [],
        _method: 'put',
    });
    form.clearErrors();
    modalOpen.value = true;
}

function closeModal() {
    modalOpen.value = false;
    resetForm();
}

function submit() {
    const url = form.id ? `/manage/products/${form.id}` : '/manage/products';
    form.post(url, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
}

function destroyProduct(product) {
    const name = productTitle(product);

    if (confirm(t('products.delete_confirm', { name }))) {
        router.delete(`/manage/products/${product.id}`, { preserveScroll: true });
    }
}

function productTitle(product) {
    return locale.value === 'ar' ? (product.title_ar || product.title_en) : (product.title_en || product.title_ar);
}

function productSecondaryTitle(product) {
    return locale.value === 'ar' ? product.title_en : product.title_ar;
}

function statusClass(status) {
    return {
        active: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100',
        inactive: 'bg-slate-100 text-slate-600 ring-1 ring-slate-200',
        draft: 'bg-amber-50 text-amber-700 ring-1 ring-amber-100',
    }[status] || 'bg-slate-50 text-slate-600';
}
</script>

<template>
    <Head :title="t('products.title')" />

    <AdminLayout>
        <div class="mb-6 flex min-w-0 flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <h1 class="text-2xl font-semibold text-slate-950">{{ t('products.title') }}</h1>
            </div>
            <button class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700" type="button" @click="openCreateModal">
                <PackagePlus class="h-4 w-4" />
                {{ t('products.new') }}
            </button>
        </div>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <IndexFilters action="/manage/products" :filters="filters" :fields="filterFields" />

            <div class="overflow-x-auto lg:hidden">
                <table class="min-w-[720px] w-full text-start text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-3 text-start">{{ t('dashboard.product') }}</th>
                            <th class="px-4 py-3 text-start">{{ t('common.price') }}</th>
                            <th class="px-4 py-3 text-start">{{ t('common.status') }}</th>
                            <th class="px-4 py-3 text-end">{{ t('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="product in productItems" :key="`mobile-${product.id}`" class="hover:bg-slate-50/70">
                            <td class="max-w-[260px] px-4 py-3">
                                <p class="break-words font-semibold text-slate-900">{{ productTitle(product) }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ product.sku || t('common.no_sku') }}</p>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-900">{{ Number(product.price).toFixed(2) }} {{ product.currency }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-md px-2 py-1 text-xs font-semibold capitalize" :class="statusClass(product.status)">{{ statusLabel(product.status) }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <Link class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 text-slate-600 hover:bg-slate-50" :href="`/manage/products/${product.id}`" :title="t('common.view')">
                                        <Eye class="h-4 w-4" />
                                    </Link>
                                    <button class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 text-slate-600 hover:bg-slate-50" type="button" :title="t('common.edit')" @click="editProduct(product)">
                                        <Edit3 class="h-4 w-4" />
                                    </button>
                                    <button class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-red-200 text-red-600 hover:bg-red-50" type="button" :title="t('common.delete')" @click="destroyProduct(product)">
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="hidden divide-y divide-slate-100 lg:block">
                <article v-for="product in productItems" :key="product.id" class="min-w-0 px-5 py-4 transition hover:bg-slate-50/70">
                    <div class="grid min-w-0 gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(180px,240px)_120px_110px_auto] lg:items-center">
                        <div class="flex min-w-0 items-start gap-3">
                            <img v-if="product.cover_url" :src="product.cover_url" class="h-11 w-11 shrink-0 rounded-lg object-cover" alt="">
                            <div v-else class="grid h-11 w-11 shrink-0 place-items-center rounded-lg bg-emerald-50 text-emerald-700">
                                <PackagePlus class="h-5 w-5" />
                            </div>
                            <div class="min-w-0">
                                <p class="break-words font-semibold text-slate-900">{{ productTitle(product) }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ product.sku || t('common.no_sku') }}</p>
                                <p class="mt-2 break-words text-sm text-slate-500 sm:hidden" :dir="locale === 'ar' ? 'ltr' : 'rtl'">{{ productSecondaryTitle(product) }}</p>
                            </div>
                        </div>

                        <div>
                            <p class="whitespace-nowrap text-sm font-semibold text-slate-900">{{ Number(product.price).toFixed(2) }} {{ product.currency }}</p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-md px-2 py-1 text-xs font-semibold capitalize" :class="statusClass(product.status)">{{ statusLabel(product.status) }}</span>
                        </div>

                        <div class="flex justify-start gap-2 lg:justify-end">
                            <Link class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 text-slate-600 hover:bg-slate-50" :href="`/manage/products/${product.id}`" :title="t('common.view')">
                                <Eye class="h-4 w-4" />
                            </Link>
                            <button class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 text-slate-600 hover:bg-slate-50" type="button" :title="t('common.edit')" @click="editProduct(product)">
                                <Edit3 class="h-4 w-4" />
                            </button>
                            <button class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-red-200 text-red-600 hover:bg-red-50" type="button" :title="t('common.delete')" @click="destroyProduct(product)">
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </article>

                <div v-if="productItems.length === 0" class="px-5 py-12 text-center text-sm text-slate-500">
                    {{ t('products.empty') }}
                </div>
            </div>

            <div v-if="productItems.length === 0" class="px-5 py-12 text-center text-sm text-slate-500 lg:hidden">
                {{ t('products.empty') }}
            </div>

            <Pagination :paginator="products" />
        </section>

        <Modal
            :show="modalOpen"
            :title="form.id ? t('products.edit') : t('products.create')"
            :description="t('products.modal_description')"
            width="max-w-4xl"
            @close="closeModal"
        >
            <form class="grid gap-5" @submit.prevent="submit">
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="block">
                        <span class="mb-1 block text-xs font-semibold text-slate-600">SKU</span>
                        <input v-model="form.sku" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-500" type="text">
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-semibold text-slate-600">{{ t('common.status') }}</span>
                        <select v-model="form.status" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-500">
                            <option value="draft">{{ statusLabel('draft') }}</option>
                            <option value="active">{{ statusLabel('active') }}</option>
                            <option value="inactive">{{ statusLabel('inactive') }}</option>
                        </select>
                    </label>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <label class="block">
                        <span class="mb-1 block text-xs font-semibold text-slate-600">{{ t('common.title_en') }}</span>
                        <input v-model="form.title_en" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-500" type="text">
                        <span v-if="form.errors.title_en" class="mt-1 block text-xs text-red-600">{{ form.errors.title_en }}</span>
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-semibold text-slate-600">{{ t('common.title_ar') }}</span>
                        <input v-model="form.title_ar" dir="rtl" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-500" type="text">
                        <span v-if="form.errors.title_ar" class="mt-1 block text-xs text-red-600">{{ form.errors.title_ar }}</span>
                    </label>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <label class="block">
                        <span class="mb-1 block text-xs font-semibold text-slate-600">{{ t('common.short_description_en') }}</span>
                        <textarea v-model="form.short_description_en" class="min-h-24 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-500" />
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-semibold text-slate-600">{{ t('common.short_description_ar') }}</span>
                        <textarea v-model="form.short_description_ar" dir="rtl" class="min-h-24 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-500" />
                    </label>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <label class="block">
                        <span class="mb-1 block text-xs font-semibold text-slate-600">{{ t('common.description_en') }}</span>
                        <textarea v-model="form.description_en" class="min-h-28 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-500" />
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-semibold text-slate-600">{{ t('common.description_ar') }}</span>
                        <textarea v-model="form.description_ar" dir="rtl" class="min-h-28 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-500" />
                    </label>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <label class="block">
                        <span class="mb-1 block text-xs font-semibold text-slate-600">{{ t('common.price') }}</span>
                        <input v-model="form.price" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-500" min="0" step="0.01" type="number">
                        <span v-if="form.errors.price" class="mt-1 block text-xs text-red-600">{{ form.errors.price }}</span>
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-semibold text-slate-600">{{ t('common.currency') }}</span>
                        <input v-model="form.currency" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm uppercase outline-none focus:border-emerald-500" maxlength="3" type="text">
                    </label>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <label class="block rounded-lg border border-dashed border-slate-300 p-3">
                        <span class="mb-2 flex items-center gap-2 text-xs font-semibold text-slate-600">
                            <Image class="h-4 w-4" />
                            {{ t('common.cover_image') }}
                        </span>
                        <input class="max-w-full text-xs" type="file" accept="image/*" @input="form.cover = $event.target.files[0]">
                    </label>

                    <label class="block rounded-lg border border-dashed border-slate-300 p-3">
                        <span class="mb-2 flex items-center gap-2 text-xs font-semibold text-slate-600">
                            <FileText class="h-4 w-4" />
                            {{ t('common.documents') }}
                        </span>
                        <input class="max-w-full text-xs" type="file" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip" @input="form.documents = Array.from($event.target.files)">
                    </label>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:justify-end">
                    <button type="button" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50" @click="closeModal">
                        <X class="h-4 w-4" />
                        {{ t('common.cancel') }}
                    </button>
                    <button class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60" :disabled="form.processing">
                        <Save class="h-4 w-4" />
                        {{ form.id ? t('common.save_changes') : t('products.create') }}
                    </button>
                </div>
            </form>
        </Modal>
    </AdminLayout>
</template>
