<script setup>
import { X } from 'lucide-vue-next';

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
    width: {
        type: String,
        default: 'max-w-2xl',
    },
});

const emit = defineEmits(['close']);
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="duration-150 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="duration-100 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/40 px-4 py-6 backdrop-blur-sm sm:px-6" @click.self="emit('close')">
                <div class="mx-auto w-full rounded-lg border border-slate-200 bg-white shadow-xl" :class="width">
                    <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
                        <div class="min-w-0">
                            <h2 class="text-lg font-semibold text-slate-950">{{ title }}</h2>
                            <p v-if="description" class="mt-1 text-sm text-slate-500">{{ description }}</p>
                        </div>
                        <button
                            type="button"
                            class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-800"
                            @click="emit('close')"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="max-h-[calc(100vh-12rem)] overflow-y-auto px-5 py-5">
                        <slot />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
