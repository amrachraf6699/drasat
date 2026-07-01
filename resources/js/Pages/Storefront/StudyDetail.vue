<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { BarChart3, Download, FileText, Landmark, LockKeyhole, ShoppingCart } from 'lucide-vue-next';
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import StudyCover from '@/Components/Storefront/StudyCover.vue';
import ProductCard from '@/Components/Storefront/ProductCard.vue';
import FaqAccordion from '@/Components/Storefront/FaqAccordion.vue';
import CartSummary from '@/Components/Storefront/CartSummary.vue';
import { useStorefrontTranslations } from '@/Composables/useStorefrontTranslations';

const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
    relatedProducts: {
        type: Array,
        default: () => [],
    },
    faqs: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const { t } = useStorefrontTranslations();
const cart = computed(() => page.props.cartSummary || { items: [], total: props.product.price, subtotal: props.product.price, count: 0 });
const documents = computed(() => props.product.documents?.length ? props.product.documents : [
    { id: 'report', name: t('product.default_file_report'), size: '12.4 MB' },
    { id: 'model', name: t('product.default_file_model'), size: '1.8 MB' },
    { id: 'summary', name: t('product.default_file_summary'), size: '1.2 MB' },
]);

function addToCart() {
    router.post('/cart/items', { product_id: props.product.id }, {
        preserveScroll: true,
    });
}
</script>

<template>
    <StorefrontLayout>
        <section class="bg-white py-8">
            <div class="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 lg:grid-cols-[1fr_390px] lg:px-8">
                <div>
                    <div class="mb-8 flex flex-wrap items-center gap-2 text-sm text-slate-500">
                        <Link href="/" class="hover:text-emerald-800">Home</Link>
                        <span>/</span>
                        <Link href="/studies" class="hover:text-emerald-800">{{ t('layout.studies') }}</Link>
                        <span>/</span>
                        <span class="text-emerald-800">{{ product.title }}</span>
                    </div>

                    <div class="grid gap-10 lg:grid-cols-[0.42fr_0.58fr]">
                        <StudyCover :product="product" />
                        <div class="py-4">
                            <h1 class="text-4xl font-semibold leading-tight text-slate-950">{{ product.title }}</h1>
                            <p class="mt-4 text-2xl font-semibold text-emerald-800">{{ product.price }}</p>
                            <p class="mt-5 max-w-2xl text-base leading-8 text-slate-600">{{ product.description || product.short_description }}</p>

                            <div class="mt-7 flex flex-wrap gap-6 border-y border-slate-200 py-5 text-sm text-slate-600">
                                <span class="inline-flex items-center gap-2"><FileText class="h-5 w-5" /> {{ t('product.pdf') }}</span>
                                <span class="inline-flex items-center gap-2"><Download class="h-5 w-5" /> {{ t('product.pages') }}</span>
                                <span class="inline-flex items-center gap-2"><BarChart3 class="h-5 w-5" /> {{ t('product.model') }}</span>
                            </div>

                            <div class="mt-8">
                                <h2 class="text-2xl font-semibold text-slate-950">{{ t('product.included') }}</h2>
                                <div class="mt-4 divide-y divide-slate-200 overflow-hidden rounded-lg border border-slate-200">
                                    <div v-for="document in documents" :key="document.id" class="grid grid-cols-[1fr_auto] items-center gap-4 px-4 py-3 text-sm">
                                        <div class="flex min-w-0 items-center gap-3">
                                            <FileText class="h-5 w-5 shrink-0 text-emerald-700" />
                                            <span class="truncate font-medium text-slate-800">{{ document.name }}</span>
                                        </div>
                                        <span class="text-slate-500">{{ document.size }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-12 border-t border-slate-200 pt-8">
                        <h2 class="mb-4 text-2xl font-semibold text-slate-950">{{ t('product.faq') }}</h2>
                        <FaqAccordion :faqs="faqs" />
                    </div>
                </div>

                <div class="lg:sticky lg:top-28 lg:self-start">
                    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-lg">
                        <div class="flex gap-4">
                            <StudyCover :product="product" compact class="w-24 shrink-0" />
                            <div>
                                <p class="font-semibold text-slate-950">{{ product.title }}</p>
                                <p class="mt-2 text-lg font-semibold text-emerald-800">{{ product.price }}</p>
                                <p class="mt-2 text-sm text-slate-500">Qty: 1</p>
                            </div>
                        </div>
                        <div class="my-5 border-t border-slate-200" />
                        <button type="button" class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-lg bg-emerald-700 px-4 text-sm font-semibold text-white hover:bg-emerald-800" @click="addToCart">
                            <ShoppingCart class="h-5 w-5" />
                            {{ t('product.add_to_cart') }}
                        </button>
                        <Link href="/checkout" class="mt-3 inline-flex h-12 w-full items-center justify-center gap-2 rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-800 hover:border-emerald-700 hover:text-emerald-800">
                            <LockKeyhole class="h-5 w-5" />
                            {{ t('product.continue_checkout') }}
                        </Link>
                        <div class="mt-5 space-y-3 border-t border-slate-200 pt-5">
                            <p class="font-semibold text-slate-950">{{ t('checkout.payment_method') }}</p>
                            <div class="rounded-lg border border-emerald-700 p-4 text-sm">
                                {{ t('checkout.paypal') }}
                            </div>
                            <div class="rounded-lg border border-slate-200 p-4 text-sm">
                                <span class="inline-flex items-center gap-2"><Landmark class="h-4 w-4" /> {{ t('checkout.bank_transfer') }}</span>
                            </div>
                            <div class="rounded-lg bg-emerald-50 p-4 text-sm leading-6 text-emerald-900">
                                {{ t('checkout.access_note') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section v-if="relatedProducts.length" class="bg-slate-50 py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <h2 class="mb-6 text-2xl font-semibold text-slate-950">{{ t('product.related') }}</h2>
                <div class="grid gap-5 md:grid-cols-3">
                    <ProductCard v-for="related in relatedProducts" :key="related.id" :product="related" />
                </div>
            </div>
        </section>
    </StorefrontLayout>
</template>
