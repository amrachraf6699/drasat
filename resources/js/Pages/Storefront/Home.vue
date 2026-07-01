<script setup>
import { Link } from '@inertiajs/vue3';
import { Download, LockKeyhole, Search, ShieldCheck } from 'lucide-vue-next';
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import ProductCard from '@/Components/Storefront/ProductCard.vue';
import FaqAccordion from '@/Components/Storefront/FaqAccordion.vue';
import { useStorefrontTranslations } from '@/Composables/useStorefrontTranslations';

defineProps({
    products: {
        type: Array,
        default: () => [],
    },
    faqs: {
        type: Array,
        default: () => [],
    },
});

const { t } = useStorefrontTranslations();
</script>

<template>
    <StorefrontLayout>
        <section class="border-b border-slate-200 bg-white">
            <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 lg:grid-cols-[0.85fr_1.15fr] lg:px-8 lg:py-20">
                <div class="flex flex-col justify-center">
                    <h1 class="max-w-2xl text-5xl font-semibold leading-tight tracking-normal text-slate-950 md:text-6xl">
                        {{ t('home.title') }}
                    </h1>
                    <p class="mt-6 max-w-xl text-lg leading-8 text-slate-600">
                        {{ t('home.subtitle') }}
                    </p>
                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <Link href="/studies" class="inline-flex h-14 items-center justify-center gap-3 rounded-lg bg-emerald-700 px-7 text-base font-semibold text-white shadow-sm transition hover:bg-emerald-800">
                            <Search class="h-6 w-6" />
                            {{ t('home.browse') }}
                        </Link>
                        <Link href="/library" class="inline-flex h-14 items-center justify-center gap-3 rounded-lg border border-slate-300 bg-white px-7 text-base font-semibold text-slate-900 transition hover:border-emerald-700 hover:text-emerald-800">
                            {{ t('home.library') }}
                        </Link>
                    </div>
                    <div class="mt-14 grid gap-5 sm:grid-cols-3">
                        <div class="border-slate-200 sm:border-e sm:pe-5">
                            <ShieldCheck class="mb-3 h-9 w-9 text-emerald-700" />
                            <p class="text-sm font-semibold text-slate-950">{{ t('home.vetted') }}</p>
                            <p class="mt-2 text-sm leading-5 text-slate-500">{{ t('home.vetted_text') }}</p>
                        </div>
                        <div class="border-slate-200 sm:border-e sm:px-5">
                            <Download class="mb-3 h-9 w-9 text-emerald-700" />
                            <p class="text-sm font-semibold text-slate-950">{{ t('home.instant') }}</p>
                            <p class="mt-2 text-sm leading-5 text-slate-500">{{ t('home.instant_text') }}</p>
                        </div>
                        <div class="sm:ps-5">
                            <LockKeyhole class="mb-3 h-9 w-9 text-emerald-700" />
                            <p class="text-sm font-semibold text-slate-950">{{ t('home.secure') }}</p>
                            <p class="mt-2 text-sm leading-5 text-slate-500">{{ t('home.secure_text') }}</p>
                        </div>
                    </div>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <ProductCard v-for="product in products" :key="product.id" :product="product" />
                </div>
            </div>
        </section>

        <section class="bg-white py-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-lg border border-slate-200 p-4">
                    <div class="grid gap-4 lg:grid-cols-[1.5fr_1fr_1fr_1fr_auto]">
                        <div class="flex h-12 items-center gap-3 rounded-lg border border-slate-200 px-4 text-sm text-slate-500">
                            <Search class="h-5 w-5" />
                            {{ t('studies.search_placeholder') }}
                        </div>
                        <div class="rounded-lg border border-slate-200 px-4 py-3 text-sm text-slate-600">Category · All</div>
                        <div class="rounded-lg border border-slate-200 px-4 py-3 text-sm text-slate-600">Industry · All</div>
                        <div class="rounded-lg border border-slate-200 px-4 py-3 text-sm text-slate-600">Price · Any</div>
                        <Link href="/studies" class="inline-flex h-12 items-center justify-center rounded-lg border border-slate-300 px-5 text-sm font-semibold text-slate-800 hover:border-emerald-700 hover:text-emerald-800">
                            {{ t('home.browse') }}
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <section id="faq" class="bg-slate-50 py-12">
            <div class="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 lg:grid-cols-[0.35fr_0.65fr] lg:px-8">
                <h2 class="text-3xl font-semibold leading-tight text-slate-950">{{ t('product.faq') }}</h2>
                <FaqAccordion :faqs="faqs" />
            </div>
        </section>
    </StorefrontLayout>
</template>
