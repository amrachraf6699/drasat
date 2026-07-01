<script setup>
import { router } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import StudyCover from '@/Components/Storefront/StudyCover.vue';
import CartSummary from '@/Components/Storefront/CartSummary.vue';
import { useStorefrontTranslations } from '@/Composables/useStorefrontTranslations';

defineProps({
    cart: {
        type: Object,
        required: true,
    },
});

const { t } = useStorefrontTranslations();

function removeItem(item) {
    router.delete(`/cart/items/${item.id}`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <StorefrontLayout>
        <section class="bg-white py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <h1 class="text-4xl font-semibold text-slate-950">{{ t('cart.title') }}</h1>
                <p class="mt-3 text-lg text-slate-600">{{ t('cart.subtitle') }}</p>

                <div v-if="cart.items.length" class="mt-8 grid gap-8 lg:grid-cols-[1fr_360px]">
                    <div class="space-y-4">
                        <article v-for="item in cart.items" :key="item.id" class="grid gap-4 rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-[120px_1fr_auto]">
                            <StudyCover :product="item.product" compact />
                            <div class="min-w-0">
                                <p class="text-xl font-semibold text-slate-950">{{ item.product.title }}</p>
                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ item.product.short_description }}</p>
                                <p class="mt-3 text-sm text-slate-500">Qty: {{ item.quantity }}</p>
                            </div>
                            <div class="flex flex-row items-center justify-between gap-4 sm:flex-col sm:items-end">
                                <p class="text-lg font-semibold text-emerald-800">{{ item.total }}</p>
                                <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:border-red-300 hover:text-red-700" @click="removeItem(item)">
                                    <Trash2 class="h-4 w-4" />
                                    {{ t('common.remove') }}
                                </button>
                            </div>
                        </article>
                    </div>
                    <CartSummary :cart="cart" />
                </div>
                <div v-else class="mt-8 rounded-lg border border-dashed border-slate-300 p-12 text-center">
                    <p class="text-lg font-semibold text-slate-950">{{ t('cart.empty') }}</p>
                    <a href="/studies" class="mt-5 inline-flex h-11 items-center rounded-lg bg-emerald-700 px-5 text-sm font-semibold text-white">{{ t('home.browse') }}</a>
                </div>
            </div>
        </section>
    </StorefrontLayout>
</template>
