<script setup>
import { Link } from '@inertiajs/vue3';
import { Download, LockKeyhole, Search, ShieldCheck } from 'lucide-vue-next';
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import ProductCard from '@/Components/Storefront/ProductCard.vue';
import { useStorefrontTranslations } from '@/Composables/useStorefrontTranslations';

defineProps({
    products: {
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
                </div>

                <div v-if="products.length" class="grid gap-5 md:grid-cols-2">
                    <ProductCard v-for="product in products" :key="product.id" :product="product" />
                </div>
                <div v-else class="flex min-h-80 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
                    <div>
                        <p class="text-lg font-semibold text-slate-950">{{ t('studies.empty') }}</p>
                        <Link href="/studies" class="mt-5 inline-flex h-11 items-center justify-center rounded-lg bg-emerald-700 px-5 text-sm font-semibold text-white hover:bg-emerald-800">
                            {{ t('home.browse') }}
                        </Link>
                    </div>
                </div>
            </div>
        </section>
    </StorefrontLayout>
</template>
