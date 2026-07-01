<script setup>
import { Link } from '@inertiajs/vue3';
import { LockKeyhole, PlusCircle } from 'lucide-vue-next';
import { useStorefrontTranslations } from '@/Composables/useStorefrontTranslations';

defineProps({
    cart: {
        type: Object,
        required: true,
    },
    checkout: {
        type: Boolean,
        default: false,
    },
    bare: {
        type: Boolean,
        default: false,
    },
});

const { t } = useStorefrontTranslations();
</script>

<template>
    <aside :class="bare ? '' : 'rounded-lg border border-slate-200 bg-white p-5 shadow-sm'">
        <div class="space-y-4">
            <div class="flex items-center justify-between text-sm text-slate-600">
                <span>{{ t('cart.subtotal') }}</span>
                <span class="font-medium text-slate-950">{{ cart.subtotal }}</span>
            </div>
            <div class="border-t border-slate-200 pt-4">
                <div class="flex items-center justify-between">
                    <span class="text-base font-semibold text-slate-950">{{ t('cart.total') }}</span>
                    <span class="text-xl font-semibold text-emerald-800">{{ cart.total }}</span>
                </div>
            </div>
            <Link
                v-if="!checkout"
                href="/checkout"
                class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-lg bg-emerald-700 px-4 text-sm font-semibold text-white transition hover:bg-emerald-800"
            >
                <LockKeyhole class="h-5 w-5" />
                {{ t('cart.checkout') }}
            </Link>
            <Link
                href="/studies"
                class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-800 transition hover:border-emerald-700 hover:text-emerald-800"
            >
                <PlusCircle class="h-5 w-5" />
                {{ t('cart.add_another') }}
            </Link>
        </div>
    </aside>
</template>
