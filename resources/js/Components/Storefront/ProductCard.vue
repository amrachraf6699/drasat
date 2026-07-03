<script setup>
import { Link, router } from '@inertiajs/vue3';
import { CheckCircle2, Eye, FileText, ShoppingCart } from 'lucide-vue-next';
import StudyCover from './StudyCover.vue';
import { useStorefrontTranslations } from '@/Composables/useStorefrontTranslations';

defineProps({
    product: {
        type: Object,
        required: true,
    },
});

const { t } = useStorefrontTranslations();

function addToCart(product) {
    router.post('/cart/items', { product_id: product.id }, {
        preserveScroll: true,
    });
}
</script>

<template>
    <article class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
        <Link :href="product.href" class="block border-b border-slate-200 bg-slate-50/80 p-6">
            <StudyCover :product="product" />
        </Link>
        <div class="space-y-4 p-5">
            <div>
                <Link :href="product.href" class="text-xl font-semibold leading-tight text-slate-950 hover:text-emerald-800">
                    {{ product.title }}
                </Link>
                <p class="mt-2 min-h-12 text-sm leading-6 text-slate-600">
                    {{ product.short_description || product.description }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-4 text-sm text-slate-500">
                <span v-if="product.document_extensions?.length" class="inline-flex items-center gap-2">
                    <FileText class="h-4 w-4" />
                    {{ product.document_extensions.join(', ') }}
                </span>
                <span>{{ t('studies.documents', { count: product.documents_count || 0 }) }}</span>
            </div>

            <div class="flex items-center justify-between gap-3">
                <p class="text-xl font-semibold text-emerald-800">{{ product.price }}</p>
                <div v-if="product.is_purchased" class="inline-flex items-center gap-2 text-sm font-semibold text-emerald-700">
                    <CheckCircle2 class="h-4 w-4" />
                    {{ t('studies.purchased') }}
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-[44px_1fr]">
                <Link :href="product.href" class="grid h-11 place-items-center rounded-lg border border-slate-300 text-slate-700 transition hover:border-emerald-700 hover:text-emerald-800" :aria-label="t('common.view')" :title="t('common.view')">
                    <Eye class="h-5 w-5" />
                </Link>
                <Link
                    v-if="product.is_purchased"
                    href="/library"
                    class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-emerald-700 px-4 text-sm font-semibold text-emerald-800 transition hover:bg-emerald-50"
                >
                    <CheckCircle2 class="h-5 w-5" />
                    {{ t('layout.library') }}
                </Link>
                <button
                    v-else
                    type="button"
                    class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-emerald-700 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800"
                    @click="addToCart(product)"
                >
                    <ShoppingCart class="h-5 w-5" />
                    {{ t('studies.add_to_cart') }}
                </button>
            </div>
        </div>
    </article>
</template>
