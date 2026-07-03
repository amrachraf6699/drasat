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

function answerId(faq) {
    return `faq-answer-${faq.id}`;
}

function questionId(faq) {
    return `faq-question-${faq.id}`;
}
</script>

<template>
    <div class="divide-y divide-slate-200 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <section v-for="faq in faqs" :key="faq.id">
            <button
                :id="questionId(faq)"
                type="button"
                class="flex w-full items-center justify-between gap-4 px-5 py-5 text-start text-base font-semibold leading-6 text-slate-950 transition hover:bg-slate-50 focus:outline-none focus-visible:bg-emerald-50"
                :aria-controls="answerId(faq)"
                :aria-expanded="openId === faq.id"
                @click="openId = openId === faq.id ? null : faq.id"
            >
                <span class="min-w-0 break-words pe-4">{{ faq.question }}</span>
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-slate-100 text-slate-500 transition" :class="openId === faq.id ? 'bg-emerald-700 text-white' : ''">
                    <ChevronDown class="h-4 w-4 transition" :class="openId === faq.id ? 'rotate-180' : ''" />
                </span>
            </button>
            <div
                v-if="openId === faq.id"
                :id="answerId(faq)"
                role="region"
                :aria-labelledby="questionId(faq)"
                class="px-5 pb-6 text-sm leading-7 text-slate-600 md:pe-16"
            >
                <p class="whitespace-pre-line">{{ faq.answer }}</p>
            </div>
        </section>
    </div>
</template>
