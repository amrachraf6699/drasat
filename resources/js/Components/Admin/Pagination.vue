<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';

const props = defineProps({
    paginator: { type: Object, required: true },
});

const meta = computed(() => {
    if (props.paginator.meta) {
        return props.paginator.meta;
    }

    return {
        current_page: props.paginator.current_page,
        from: props.paginator.from,
        last_page: props.paginator.last_page,
        to: props.paginator.to,
        total: props.paginator.total,
    };
});

const prevUrl = computed(() => props.paginator.links?.prev || props.paginator.prev_page_url);
const nextUrl = computed(() => props.paginator.links?.next || props.paginator.next_page_url);

const hasPages = computed(() => (meta.value.last_page || 1) > 1);

function pageUrl(page) {
    if (!page || page < 1 || page > meta.value.last_page || page === meta.value.current_page) {
        return null;
    }

    const url = new URL(window.location.href);
    url.searchParams.set('page', page);

    return `${url.pathname}${url.search}${url.hash}`;
}
</script>

<template>
    <div
        v-if="meta.total"
        class="flex flex-col gap-3 border-t border-slate-200 px-4 py-3 text-sm text-slate-600 sm:flex-row sm:items-center sm:justify-between"
    >
        <p class="text-xs font-medium text-slate-500">
            {{ meta.from || 0 }}-{{ meta.to || 0 }} / {{ meta.total }}
        </p>

        <div v-if="hasPages" class="flex items-center gap-1">
            <Link
                :href="prevUrl || pageUrl(meta.current_page - 1) || '#'"
                preserve-scroll
                class="grid h-9 w-9 place-items-center rounded-md border border-slate-200 text-slate-600 transition hover:bg-slate-50"
                :class="{ 'pointer-events-none opacity-40': !prevUrl && !pageUrl(meta.current_page - 1) }"
                aria-label="Previous page"
            >
                <ChevronLeft class="h-4 w-4" />
            </Link>

            <template v-for="page in meta.last_page" :key="page">
                <Link
                    v-if="meta.last_page <= 7 || page === 1 || page === meta.last_page || Math.abs(page - meta.current_page) <= 1"
                    :href="pageUrl(page) || '#'"
                    preserve-scroll
                    class="grid h-9 min-w-9 place-items-center rounded-md border px-2 text-xs font-semibold transition"
                    :class="page === meta.current_page ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-slate-200 text-slate-600 hover:bg-slate-50'"
                >
                    {{ page }}
                </Link>
                <span
                    v-else-if="page === 2 || page === meta.last_page - 1"
                    class="grid h-9 min-w-9 place-items-center px-1 text-xs text-slate-400"
                >
                    ...
                </span>
            </template>

            <Link
                :href="nextUrl || pageUrl(meta.current_page + 1) || '#'"
                preserve-scroll
                class="grid h-9 w-9 place-items-center rounded-md border border-slate-200 text-slate-600 transition hover:bg-slate-50"
                :class="{ 'pointer-events-none opacity-40': !nextUrl && !pageUrl(meta.current_page + 1) }"
                aria-label="Next page"
            >
                <ChevronRight class="h-4 w-4" />
            </Link>
        </div>
    </div>
</template>
