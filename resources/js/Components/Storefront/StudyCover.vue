<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { FileText } from 'lucide-vue-next';
import { useStorefrontTranslations } from '@/Composables/useStorefrontTranslations';

const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
    compact: {
        type: Boolean,
        default: false,
    },
});

const page = usePage();
const { t } = useStorefrontTranslations();
const publicSettings = computed(() => page.props.publicSettings || {});
const siteName = computed(() => publicSettings.value.general?.site_name || t('common.brand'));
const siteLogoUrl = computed(() => publicSettings.value.general?.site_logo_url);
const siteInitial = computed(() => (siteName.value || 'D').trim().charAt(0).toUpperCase());
const titleLines = computed(() => String(props.product?.title || t('layout.tagline')).split(' ').slice(0, 4).join(' '));
</script>

<template>
    <div class="relative overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm" :class="compact ? 'aspect-[4/3]' : 'aspect-[3/4]'">
        <img
            v-if="product.cover_url"
            :src="product.cover_url"
            :alt="product.title"
            class="h-full w-full object-cover"
        >
        <div v-else class="relative flex h-full flex-col justify-between bg-[linear-gradient(135deg,#ffffff_0%,#f8fafc_46%,#e8f4ef_100%)] p-4">
            <div class="flex items-center gap-2 text-emerald-800">
                <span class="grid h-7 w-7 place-items-center overflow-hidden rounded-md bg-emerald-700 text-sm font-bold text-white">
                    <img v-if="siteLogoUrl" :src="siteLogoUrl" :alt="`${siteName} logo`" class="h-full w-full object-contain">
                    <span v-else>{{ siteInitial }}</span>
                </span>
                <span class="truncate text-sm font-semibold">{{ siteName }}</span>
            </div>
            <div>
                <p class="max-w-[11rem] text-xl font-semibold leading-tight text-slate-950" :class="compact ? 'text-base' : ''">
                    {{ titleLines }}
                </p>
                <p class="mt-3 text-xs font-medium text-slate-500">{{ product.sku || t('layout.tagline') }}</p>
            </div>
            <div class="absolute bottom-0 start-0 h-24 w-full bg-emerald-900 [clip-path:polygon(0_45%,58%_0,100%_100%,0_100%)]" />
            <div class="absolute bottom-3 end-3 grid h-9 w-9 place-items-center rounded-md bg-emerald-700 text-white shadow">
                <FileText class="h-5 w-5" />
            </div>
        </div>
    </div>
</template>
