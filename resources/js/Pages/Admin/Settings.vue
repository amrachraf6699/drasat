<script setup>
import { computed, reactive, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { Image, Save, Settings as SettingsIcon } from 'lucide-vue-next';

const props = defineProps({
    groups: { type: Array, required: true },
});

const { inputTypeLabel, t } = useTranslations();
const activeGroupKey = ref(props.groups[0]?.key || '');
const forms = reactive({});
const processing = reactive({});

props.groups.forEach((group) => {
    group.settings.forEach((setting) => {
        forms[setting.id] = {
            value: normalizeValue(setting),
            value_en: setting.value_en || '',
            value_ar: setting.value_ar || '',
            file: null,
            fileName: '',
        };
        processing[setting.id] = false;
    });
});

const activeGroup = computed(() => {
    return props.groups.find((group) => group.key === activeGroupKey.value) || props.groups[0] || { settings: [] };
});

function normalizeValue(setting) {
    if (setting.input_type === 'boolean') {
        return setting.value === true || setting.value === '1' || setting.value === 1;
    }

    return setting.value || '';
}

function submit(setting) {
    const form = forms[setting.id];
    const payload = { _method: 'put' };

    if (setting.input_type === 'image') {
        if (form.file) {
            payload.value = form.file;
        }
    } else if (setting.is_translatable) {
        payload.value = form.value;
        payload.value_en = form.value_en;
        payload.value_ar = form.value_ar;
    } else {
        payload.value = setting.input_type === 'boolean' ? (form.value ? '1' : '0') : form.value;
    }

    processing[setting.id] = true;

    router.post(`/manage/settings/${setting.id}`, payload, {
        forceFormData: setting.input_type === 'image',
        preserveScroll: true,
        onFinish: () => {
            processing[setting.id] = false;
        },
        onSuccess: () => {
            form.file = null;
            form.fileName = '';
        },
    });
}

function updateFile(setting, event) {
    const file = event.target.files?.[0] || null;
    forms[setting.id].file = file;
    forms[setting.id].fileName = file?.name || '';
}
</script>

<template>
    <Head :title="t('settings.title')" />

    <AdminLayout>
        <div class="mb-6 min-w-0">
            <h1 class="text-2xl font-semibold text-slate-950">{{ t('settings.title') }}</h1>
        </div>

        <section class="min-w-0 rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto border-b border-slate-200 px-4 py-3">
                <div class="flex min-w-max gap-2">
                    <button
                        v-for="group in groups"
                        :key="group.key"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm font-semibold transition"
                        :class="activeGroupKey === group.key ? 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-100' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950'"
                        @click="activeGroupKey = group.key"
                    >
                        <SettingsIcon class="h-4 w-4" />
                        {{ group.label }}
                    </button>
                </div>
            </div>

            <div class="divide-y divide-slate-100">
                <form
                    v-for="setting in activeGroup.settings"
                    :key="setting.id"
                    class="grid min-w-0 gap-4 px-5 py-5 lg:grid-cols-[minmax(220px,0.36fr)_minmax(0,1fr)_auto] lg:items-start"
                    @submit.prevent="submit(setting)"
                >
                    <div class="min-w-0">
                        <p class="break-words text-sm font-semibold text-slate-950">{{ setting.label }}</p>
                        <p v-if="setting.help" class="mt-2 break-words text-sm leading-6 text-slate-500">{{ setting.help }}</p>
                    </div>

                    <div class="min-w-0">
                        <div v-if="setting.input_type === 'image'" class="grid gap-3">
                            <div v-if="setting.value_url" class="flex items-center gap-3">
                                <img :src="setting.value_url" class="h-14 w-14 rounded-lg border border-slate-200 object-contain" alt="">
                                <p class="min-w-0 break-all text-xs text-slate-500">{{ setting.value }}</p>
                            </div>
                            <label class="flex min-h-24 cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border border-dashed border-slate-300 px-4 py-5 text-center hover:border-emerald-300 hover:bg-emerald-50/30">
                                <Image class="h-6 w-6 text-slate-500" />
                                <span class="text-sm font-semibold text-slate-700">{{ forms[setting.id].fileName || t('settings.choose_logo') }}</span>
                                <input class="sr-only" type="file" accept="image/*" @change="updateFile(setting, $event)">
                            </label>
                        </div>

                        <label v-else-if="setting.input_type === 'boolean'" class="flex items-center gap-2 text-sm text-slate-700">
                            <input v-model="forms[setting.id].value" class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" type="checkbox">
                            {{ t('settings.enabled') }}
                        </label>

                        <textarea
                            v-else-if="setting.input_type === 'textarea' && !setting.is_translatable"
                            v-model="forms[setting.id].value"
                            class="min-h-28 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-500"
                        />

                        <div v-else-if="setting.is_translatable" class="grid gap-3 md:grid-cols-2">
                            <label class="block">
                                <span class="mb-1 block text-xs font-semibold text-slate-600">{{ t('common.value_en') }}</span>
                                <input v-model="forms[setting.id].value_en" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-500" type="text">
                            </label>
                            <label class="block">
                                <span class="mb-1 block text-xs font-semibold text-slate-600">{{ t('common.value_ar') }}</span>
                                <input v-model="forms[setting.id].value_ar" dir="rtl" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-500" type="text">
                            </label>
                        </div>

                        <input
                            v-else
                            v-model="forms[setting.id].value"
                            class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-500"
                            :type="{ email: 'email', url: 'url', number: 'number' }[setting.input_type] || 'text'"
                        >
                    </div>

                    <div class="flex justify-start lg:justify-end">
                        <button
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60"
                            :disabled="processing[setting.id]"
                        >
                            <Save class="h-4 w-4" />
                            {{ t('common.save_changes') }}
                        </button>
                    </div>
                </form>

                <div v-if="activeGroup.settings.length === 0" class="px-5 py-12 text-center text-sm text-slate-500">
                    {{ t('settings.empty') }}
                </div>
            </div>
        </section>
    </AdminLayout>
</template>
