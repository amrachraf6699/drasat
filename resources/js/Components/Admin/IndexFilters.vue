<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { ChevronDown, Search, SlidersHorizontal, X } from 'lucide-vue-next';
import { useTranslations } from '@/Composables/useTranslations';

const props = defineProps({
    action: { type: String, required: true },
    filters: { type: Object, default: () => ({}) },
    fields: { type: Array, default: () => [] },
});

const { t } = useTranslations();

const form = reactive({});
const isOpen = ref(hasActiveFilters());

const activeCount = computed(() => {
    return Object.values(cleanedPayload()).length;
});

function syncFilters() {
    form.q = props.filters.q || '';

    props.fields.forEach((field) => {
        form[field.key] = props.filters[field.key] || '';
    });
}

syncFilters();

watch(() => props.filters, () => {
    syncFilters();

    if (hasActiveFilters()) {
        isOpen.value = true;
    }
}, { deep: true });

function cleanedPayload() {
    return Object.fromEntries(
        Object.entries(form).filter(([, value]) => value !== null && value !== undefined && value !== ''),
    );
}

function submit() {
    router.get(props.action, cleanedPayload(), {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
}

function reset() {
    form.q = '';
    props.fields.forEach((field) => {
        form[field.key] = '';
    });
    submit();
}

function hasActiveFilters() {
    return Object.entries(props.filters || {}).some(([, value]) => value !== null && value !== undefined && value !== '');
}
</script>

<template>
    <div class="border-b border-slate-200 bg-white">
        <div class="flex items-center justify-between gap-3 px-5 py-3">
            <button
                type="button"
                class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                @click="isOpen = !isOpen"
            >
                <SlidersHorizontal class="h-4 w-4" />
                {{ t('common.filters') }}
                <span v-if="activeCount" class="grid h-5 min-w-5 place-items-center rounded-full bg-emerald-600 px-1.5 text-[11px] font-bold text-white">
                    {{ activeCount }}
                </span>
                <ChevronDown class="h-4 w-4 text-slate-400 transition" :class="{ 'rotate-180': isOpen }" />
            </button>

            <button
                v-if="activeCount"
                type="button"
                class="inline-flex h-10 items-center gap-2 rounded-lg px-3 text-sm font-semibold text-slate-500 transition hover:bg-slate-50 hover:text-slate-800"
                @click="reset"
            >
                <X class="h-4 w-4" />
                {{ t('common.reset') }}
            </button>
        </div>

        <form v-if="isOpen" class="grid gap-3 border-t border-slate-100 bg-slate-50/60 px-5 py-4 lg:grid-cols-[minmax(240px,1fr)_repeat(auto-fit,minmax(160px,220px))_auto]" @submit.prevent="submit">
            <label class="relative block min-w-0">
                <Search class="absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <input
                    v-model="form.q"
                    class="h-10 w-full rounded-lg border border-slate-200 bg-white pe-3 ps-9 text-sm outline-none transition focus:border-emerald-500"
                    type="search"
                    :placeholder="t('common.search')"
                >
            </label>

            <label v-for="field in fields" :key="field.key" class="block min-w-0">
                <select
                    v-model="form[field.key]"
                    class="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-emerald-500"
                    @change="submit"
                >
                    <option value="">{{ field.label }}</option>
                    <option v-for="option in field.options" :key="option.value" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>
            </label>

            <div class="flex items-center gap-2">
                <button type="submit" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 text-sm font-semibold text-white hover:bg-emerald-700">
                    <Search class="h-4 w-4" />
                    {{ t('common.search_action') }}
                </button>
            </div>
        </form>
    </div>
</template>
