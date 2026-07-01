<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import ProductCard from '@/Components/Storefront/ProductCard.vue';
import { useStorefrontTranslations } from '@/Composables/useStorefrontTranslations';

const props = defineProps({
    products: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const { t } = useStorefrontTranslations();
const form = reactive({
    q: props.filters.q || '',
    sort: props.filters.sort || 'newest',
});

function submit() {
    router.get('/studies', form, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}
</script>

<template>
    <StorefrontLayout>
        <section class="bg-white py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl">
                    <h1 class="text-4xl font-semibold text-slate-950">{{ t('studies.title') }}</h1>
                    <p class="mt-3 text-lg leading-8 text-slate-600">{{ t('studies.subtitle') }}</p>
                </div>

                <form class="mt-8 rounded-lg border border-slate-200 p-4" @submit.prevent="submit">
                    <div class="grid gap-4 lg:grid-cols-[1fr_220px_auto]">
                        <label class="flex h-12 items-center gap-3 rounded-lg border border-slate-200 px-4">
                            <Search class="h-5 w-5 text-slate-400" />
                            <input v-model="form.q" type="search" class="w-full border-0 bg-transparent text-sm text-slate-900 outline-none placeholder:text-slate-400" :placeholder="t('studies.search_placeholder')">
                        </label>
                        <select v-model="form.sort" class="h-12 rounded-lg border border-slate-200 bg-white px-4 text-sm text-slate-900 outline-none">
                            <option value="newest">{{ t('studies.newest') }}</option>
                            <option value="price_low">{{ t('studies.price_low') }}</option>
                            <option value="price_high">{{ t('studies.price_high') }}</option>
                        </select>
                        <button type="submit" class="h-12 rounded-lg bg-emerald-700 px-6 text-sm font-semibold text-white hover:bg-emerald-800">
                            {{ t('common.search') }}
                        </button>
                    </div>
                </form>

                <div class="mt-6 flex items-center justify-between text-sm text-slate-500">
                    <p>{{ t('studies.showing', { count: products.meta?.total || products.data.length }) }}</p>
                </div>

                <div v-if="products.data.length" class="mt-6 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                    <ProductCard v-for="product in products.data" :key="product.id" :product="product" />
                </div>
                <div v-else class="mt-6 rounded-lg border border-dashed border-slate-300 p-10 text-center text-slate-500">
                    {{ t('studies.empty') }}
                </div>
            </div>
        </section>
    </StorefrontLayout>
</template>
