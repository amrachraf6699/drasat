<script setup>
import { computed } from 'vue';
import { FileText } from 'lucide-vue-next';

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

const titleLines = computed(() => String(props.product?.title || 'Dirasat Study').split(' ').slice(0, 4).join(' '));
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
                <span class="grid h-7 w-7 place-items-center rounded-md bg-emerald-700 text-sm font-bold text-white">D</span>
                <span class="text-sm font-semibold">Dirasat</span>
            </div>
            <div>
                <p class="max-w-[11rem] text-xl font-semibold leading-tight text-slate-950" :class="compact ? 'text-base' : ''">
                    {{ titleLines }}
                </p>
                <p class="mt-3 text-xs font-medium text-slate-500">Financial & Operational Study</p>
            </div>
            <div class="absolute bottom-0 start-0 h-24 w-full bg-emerald-900 [clip-path:polygon(0_45%,58%_0,100%_100%,0_100%)]" />
            <div class="absolute bottom-3 end-3 grid h-9 w-9 place-items-center rounded-md bg-emerald-700 text-white shadow">
                <FileText class="h-5 w-5" />
            </div>
        </div>
    </div>
</template>
