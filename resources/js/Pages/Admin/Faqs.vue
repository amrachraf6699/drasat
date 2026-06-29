<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Modal from '@/Components/Admin/Modal.vue';
import IndexFilters from '@/Components/Admin/IndexFilters.vue';
import Pagination from '@/Components/Admin/Pagination.vue';
import { useTranslations } from '@/Composables/useTranslations';
import {
    CircleHelp,
    Edit3,
    Eye,
    PlusCircle,
    Save,
    Trash2,
    X,
    ListChecks,
    CheckCircle2,
    CircleOff,
    ArrowUpDown,
} from 'lucide-vue-next';

const props = defineProps({
    faqs: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const { locale, statusLabel, t } = useTranslations();

const modalOpen = ref(false);

const faqItems = computed(() => props.faqs.data || []);

const totalCount = computed(() => faqItems.value.length);
const activeCount = computed(() => faqItems.value.filter((faq) => faq.status === 'active').length);
const inactiveCount = computed(() => faqItems.value.filter((faq) => faq.status === 'inactive').length);

const filterFields = computed(() => [
    {
        key: 'status',
        label: t('common.all_statuses'),
        options: ['active', 'inactive'].map((status) => ({
            value: status,
            label: statusLabel(status),
        })),
    },
    {
        key: 'sort',
        label: t('common.sort_by'),
        options: [
            { value: 'sort_order', label: t('common.sort') },
            { value: 'newest', label: t('common.newest') },
            { value: 'oldest', label: t('common.oldest') },
        ],
    },
]);

const blank = {
    id: null,
    question_en: '',
    question_ar: '',
    answer_en: '',
    answer_ar: '',
    status: 'active',
    sort_order: 0,
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

function editFaq(faq) {
    Object.assign(form, { ...blank, ...faq });
    form.clearErrors();
    modalOpen.value = true;
}

function closeModal() {
    modalOpen.value = false;
    resetForm();
}

function submit() {
    if (form.id) {
        form.put(`/manage/faqs/${form.id}`, {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });

        return;
    }

    form.post('/manage/faqs', {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
}

function destroyFaq(faq) {
    const question = faqQuestion(faq);

    if (confirm(t('faqs.delete_confirm', { question }))) {
        router.delete(`/manage/faqs/${faq.id}`, {
            preserveScroll: true,
        });
    }
}

function faqQuestion(faq) {
    return locale.value === 'ar'
        ? faq.question_ar || faq.question_en
        : faq.question_en || faq.question_ar;
}

function faqAnswer(faq) {
    return locale.value === 'ar'
        ? faq.answer_ar || faq.answer_en
        : faq.answer_en || faq.answer_ar;
}

function faqSecondaryQuestion(faq) {
    return locale.value === 'ar' ? faq.question_en : faq.question_ar;
}

function statusClass(status) {
    return status === 'active'
        ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100'
        : 'bg-slate-100 text-slate-600 ring-1 ring-slate-200';
}

function statusDotClass(status) {
    return status === 'active' ? 'bg-emerald-500' : 'bg-slate-400';
}
</script>

<template>
    <Head :title="t('faqs.title')" />

    <AdminLayout>
        <div class="mb-7 flex min-w-0 flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
            <div class="min-w-0">
                <h1 class="mt-1 text-3xl font-black tracking-tight text-slate-950">
                    {{ t('faqs.title') }}
                </h1>
            </div>

            <button
                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700"
                type="button"
                @click="openCreateModal"
            >
                <PlusCircle class="h-4 w-4" />
                {{ t('faqs.new') }}
            </button>
        </div>
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-slate-50/70">
                <IndexFilters
                    action="/manage/faqs"
                    :filters="filters"
                    :fields="filterFields"
                />
            </div>

            <div class="grid gap-4 bg-slate-50/40 p-4">
                <article
                    v-for="faq in faqItems"
                    :key="faq.id"
                    class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-md"
                >
                    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-start">
                        <div class="flex min-w-0 items-start gap-4">
                            <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100">
                                <CircleHelp class="h-6 w-6" />
                            </div>

                            <div class="min-w-0" :dir="locale === 'ar' ? 'rtl' : 'ltr'">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold capitalize"
                                        :class="statusClass(faq.status)"
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full"
                                            :class="statusDotClass(faq.status)"
                                        />
                                        {{ statusLabel(faq.status) }}
                                    </span>

                                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600 ring-1 ring-slate-200">
                                        <ArrowUpDown class="h-3.5 w-3.5" />
                                        #{{ faq.sort_order }}
                                    </span>
                                </div>

                                <h3 class="mt-3 break-words text-lg font-black leading-7 text-slate-950">
                                    {{ faqQuestion(faq) }}
                                </h3>

                                <p class="mt-3 line-clamp-2 break-words text-sm leading-6 text-slate-500">
                                    {{ faqAnswer(faq) }}
                                </p>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center justify-end gap-2">
                            <Link
                                class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
                                :href="`/manage/faqs/${faq.id}`"
                                :title="t('common.view')"
                            >
                                <Eye class="h-4 w-4" />
                            </Link>

                            <button
                                class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
                                type="button"
                                :title="t('common.edit')"
                                @click="editFaq(faq)"
                            >
                                <Edit3 class="h-4 w-4" />
                            </button>

                            <button
                                class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-red-100 bg-white text-red-500 transition hover:bg-red-50 hover:text-red-700"
                                type="button"
                                :title="t('common.delete')"
                                @click="destroyFaq(faq)"
                            >
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </article>

                <div
                    v-if="faqItems.length === 0"
                    class="rounded-2xl border border-dashed border-slate-300 bg-white px-5 py-16 text-center"
                >
                    <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100">
                        <CircleHelp class="h-6 w-6" />
                    </div>

                    <p class="mt-4 text-sm font-bold text-slate-700">
                        {{ t('faqs.empty') }}
                    </p>
                </div>
            </div>

            <Pagination :paginator="faqs" />
        </section>

        <Modal
            :show="modalOpen"
            :title="form.id ? t('faqs.edit') : t('faqs.create')"
            :description="t('faqs.modal_description')"
            width="max-w-3xl"
            @close="closeModal"
        >
            <form class="grid gap-5" @submit.prevent="submit">
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="block">
                        <span class="mb-1.5 block text-xs font-bold text-slate-600">
                            {{ t('common.question_en') }}
                        </span>

                        <input
                            v-model="form.question_en"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-medium outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-50"
                            type="text"
                        >

                        <span
                            v-if="form.errors.question_en"
                            class="mt-1 block text-xs font-semibold text-red-600"
                        >
                            {{ form.errors.question_en }}
                        </span>
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-xs font-bold text-slate-600">
                            {{ t('common.question_ar') }}
                        </span>

                        <input
                            v-model="form.question_ar"
                            dir="rtl"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-medium outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-50"
                            type="text"
                        >

                        <span
                            v-if="form.errors.question_ar"
                            class="mt-1 block text-xs font-semibold text-red-600"
                        >
                            {{ form.errors.question_ar }}
                        </span>
                    </label>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <label class="block">
                        <span class="mb-1.5 block text-xs font-bold text-slate-600">
                            {{ t('common.answer_en') }}
                        </span>

                        <textarea
                            v-model="form.answer_en"
                            class="min-h-32 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-medium outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-50"
                        />

                        <span
                            v-if="form.errors.answer_en"
                            class="mt-1 block text-xs font-semibold text-red-600"
                        >
                            {{ form.errors.answer_en }}
                        </span>
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-xs font-bold text-slate-600">
                            {{ t('common.answer_ar') }}
                        </span>

                        <textarea
                            v-model="form.answer_ar"
                            dir="rtl"
                            class="min-h-32 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-medium outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-50"
                        />

                        <span
                            v-if="form.errors.answer_ar"
                            class="mt-1 block text-xs font-semibold text-red-600"
                        >
                            {{ form.errors.answer_ar }}
                        </span>
                    </label>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <label class="block">
                        <span class="mb-1.5 block text-xs font-bold text-slate-600">
                            {{ t('common.status') }}
                        </span>

                        <select
                            v-model="form.status"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-bold outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-50"
                        >
                            <option value="active">{{ statusLabel('active') }}</option>
                            <option value="inactive">{{ statusLabel('inactive') }}</option>
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-xs font-bold text-slate-600">
                            {{ t('common.sort') }}
                        </span>

                        <input
                            v-model="form.sort_order"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-bold outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-50"
                            min="0"
                            type="number"
                        >
                    </label>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-50"
                        @click="closeModal"
                    >
                        <X class="h-4 w-4" />
                        {{ t('common.cancel') }}
                    </button>

                    <button
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-700 disabled:opacity-60"
                        :disabled="form.processing"
                    >
                        <Save class="h-4 w-4" />
                        {{ form.id ? t('common.save_changes') : t('faqs.create') }}
                    </button>
                </div>
            </form>
        </Modal>
    </AdminLayout>
</template>