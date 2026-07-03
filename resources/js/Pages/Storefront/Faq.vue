<script setup>
import { computed, ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { BookOpen, CircleHelp, CreditCard, Download, Mail, Search, ShieldCheck } from 'lucide-vue-next';
import FaqAccordion from '@/Components/Storefront/FaqAccordion.vue';
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { useStorefrontTranslations } from '@/Composables/useStorefrontTranslations';

const props = defineProps({
    faqs: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const { t } = useStorefrontTranslations();
const query = ref('');

const supportEmail = computed(() => page.props.publicSettings?.general?.support_email || 'support@drasa.test');
const supportHref = computed(() => `mailto:${supportEmail.value}`);
const filteredFaqs = computed(() => {
    const needle = query.value.trim().toLowerCase();

    if (! needle) {
        return props.faqs;
    }

    return props.faqs.filter((faq) => {
        return [faq.question, faq.answer].some((value) => String(value || '').toLowerCase().includes(needle));
    });
});
const questionsCount = computed(() => t('faq.questions_count', { count: filteredFaqs.value.length }));
const topicItems = computed(() => [
    { label: t('faq.topics.payments'), icon: CreditCard },
    { label: t('faq.topics.downloads'), icon: Download },
    { label: t('faq.topics.accounts'), icon: ShieldCheck },
]);
</script>

<template>
    <Head :title="t('faq.title')" />

    <StorefrontLayout>
        <section class="border-b border-slate-200 bg-slate-50">
            <div class="mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:px-6 lg:grid-cols-[1fr_360px] lg:px-8 lg:py-16">
                <div class="flex flex-col justify-center">
                    <h1 class="max-w-3xl text-4xl font-semibold leading-tight text-slate-950 md:text-5xl">
                        {{ t('faq.title') }}
                    </h1>
                    <p class="mt-4 max-w-2xl text-lg leading-8 text-slate-600">
                        {{ t('faq.subtitle') }}
                    </p>
                    <label class="mt-8 flex h-14 max-w-2xl items-center gap-3 rounded-lg border border-slate-200 bg-white px-4 shadow-sm">
                        <Search class="h-5 w-5 shrink-0 text-slate-400" />
                        <span class="sr-only">{{ t('common.search') }}</span>
                        <input
                            v-model="query"
                            type="search"
                            class="w-full border-0 bg-transparent text-base text-slate-900 outline-none placeholder:text-slate-400"
                            :placeholder="t('faq.search_placeholder')"
                        >
                    </label>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="grid h-12 w-12 place-items-center rounded-lg bg-emerald-50 text-emerald-800">
                        <CircleHelp class="h-6 w-6" />
                    </div>
                    <h2 class="mt-5 text-xl font-semibold text-slate-950">{{ t('faq.support_title') }}</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-600">{{ t('faq.support_text') }}</p>
                    <div class="mt-6 grid gap-3">
                        <a :href="supportHref" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-emerald-800 px-4 text-sm font-semibold text-white transition hover:bg-emerald-900">
                            <Mail class="h-4 w-4" />
                            {{ t('faq.contact_support') }}
                        </a>
                        <Link href="/studies" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-900 transition hover:border-emerald-700 hover:text-emerald-800">
                            <BookOpen class="h-4 w-4" />
                            {{ t('faq.browse_studies') }}
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-white py-12">
            <div class="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 lg:grid-cols-[280px_1fr] lg:px-8">
                <aside class="space-y-4 lg:sticky lg:top-28 lg:self-start">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-5">
                        <p class="text-sm font-semibold uppercase tracking-normal text-slate-500">{{ t('faq.topics_title') }}</p>
                        <div class="mt-4 space-y-3">
                            <div v-for="item in topicItems" :key="item.label" class="flex items-center gap-3 rounded-lg bg-white px-3 py-3 text-sm font-semibold text-slate-700">
                                <component :is="item.icon" class="h-5 w-5 shrink-0 text-emerald-700" />
                                <span>{{ item.label }}</span>
                            </div>
                        </div>
                    </div>
                </aside>

                <div>
                    <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h2 class="text-2xl font-semibold text-slate-950">{{ t('faq.all_questions') }}</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ questionsCount }}</p>
                        </div>
                    </div>

                    <FaqAccordion v-if="filteredFaqs.length" :faqs="filteredFaqs" />

                    <div v-else class="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-10 text-center">
                        <Search class="mx-auto h-9 w-9 text-slate-400" />
                        <p class="mt-4 text-sm font-semibold text-slate-800">{{ t('faq.empty') }}</p>
                    </div>
                </div>
            </div>
        </section>
    </StorefrontLayout>
</template>
