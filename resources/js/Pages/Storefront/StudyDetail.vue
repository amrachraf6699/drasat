<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { CalendarDays, CheckCircle2, FileText, Hash, Landmark, LockKeyhole, ShoppingCart } from 'lucide-vue-next';
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import StudyCover from '@/Components/Storefront/StudyCover.vue';
import ProductCard from '@/Components/Storefront/ProductCard.vue';
import FaqAccordion from '@/Components/Storefront/FaqAccordion.vue';
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
const documents = computed(() => props.product.documents || []);
const documentExtensions = computed(() => {
    return [...new Set(documents.value.map((document) => document.extension).filter(Boolean))].slice(0, 4);
});
const summaryItems = computed(() => {
    const items = [
        { key: 'documents', icon: FileText, label: t('studies.documents', { count: props.product.documents_count || 0 }) },
    ];

    if (documentExtensions.value.length) {
        items.push({ key: 'formats', icon: FileText, label: documentExtensions.value.join(', ') });
    }

    if (props.product.sku) {
        items.push({ key: 'sku', icon: Hash, label: props.product.sku });
    }

    if (props.product.published_at) {
        items.push({ key: 'published', icon: CalendarDays, label: props.product.published_at });
    }

    return items;
});
const supportEmail = computed(() => page.props.publicSettings?.general?.support_email || 'support@drasa.test');

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
                        <Link href="/" class="hover:text-emerald-800">{{ t('common.home') }}</Link>
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
                                <span v-for="item in summaryItems" :key="item.key" class="inline-flex items-center gap-2">
                                    <component :is="item.icon" class="h-5 w-5" />
                                    {{ item.label }}
                                </span>
                            </div>

                            <div class="mt-8">
                                <h2 class="text-2xl font-semibold text-slate-950">{{ t('product.included') }}</h2>
                                <div v-if="documents.length" class="mt-4 divide-y divide-slate-200 overflow-hidden rounded-lg border border-slate-200">
                                    <div v-for="document in documents" :key="document.id" class="grid grid-cols-[1fr_auto] items-center gap-4 px-4 py-3 text-sm">
                                        <div class="flex min-w-0 items-center gap-3">
                                            <FileText class="h-5 w-5 shrink-0 text-emerald-700" />
                                            <div class="min-w-0">
                                                <span class="block truncate font-medium text-slate-800">{{ document.name }}</span>
                                                <span v-if="document.extension" class="mt-1 block text-xs font-semibold text-slate-500">{{ document.extension }}</span>
                                            </div>
                                        </div>
                                        <span class="text-slate-500">{{ document.size }}</span>
                                    </div>
                                </div>
                                <div v-else class="mt-4 rounded-lg border border-dashed border-slate-300 bg-slate-50 p-5 text-sm leading-6 text-slate-600">
                                    {{ t('product.no_documents') }}
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
                                <p v-if="product.sku" class="mt-2 text-sm text-slate-500">{{ product.sku }}</p>
                            </div>
                        </div>
                        <div class="my-5 border-t border-slate-200" />
                        <Link v-if="product.is_purchased" href="/library" class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-lg border border-emerald-700 px-4 text-sm font-semibold text-emerald-800 hover:bg-emerald-50">
                            <CheckCircle2 class="h-5 w-5" />
                            {{ t('layout.library') }}
                        </Link>
                        <button v-else type="button" class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-lg bg-emerald-700 px-4 text-sm font-semibold text-white hover:bg-emerald-800" @click="addToCart">
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
                            <a :href="`mailto:${supportEmail}`" class="block rounded-lg border border-slate-200 p-4 text-sm font-semibold text-slate-700 hover:border-emerald-700 hover:text-emerald-800">
                                {{ t('library.help', { email: supportEmail }) }}
                            </a>
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
