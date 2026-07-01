<script setup>
import { ref } from 'vue';
import { ChevronDown } from 'lucide-vue-next';

defineProps({
    faqs: {
        type: Array,
        default: () => [],
    },
});

const openId = ref(null);
</script>

<template>
    <div class="divide-y divide-slate-200 overflow-hidden rounded-lg border border-slate-200 bg-white">
        <section v-for="faq in faqs" :key="faq.id">
            <button
                type="button"
                class="flex w-full items-center justify-between gap-4 px-5 py-4 text-start text-sm font-semibold text-slate-950 transition hover:bg-slate-50"
                @click="openId = openId === faq.id ? null : faq.id"
            >
                <span>{{ faq.question }}</span>
                <ChevronDown class="h-5 w-5 shrink-0 text-slate-500 transition" :class="openId === faq.id ? 'rotate-180' : ''" />
            </button>
            <div v-if="openId === faq.id" class="px-5 pb-5 text-sm leading-6 text-slate-600">
                {{ faq.answer }}
            </div>
        </section>
    </div>
</template>
