<script setup>
import { computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { LockKeyhole, Mail, Save, UserRoundCog } from 'lucide-vue-next';

const page = usePage();
const { t } = useTranslations();

const admin = computed(() => page.props.auth?.admin || {});

const form = useForm({
    name: admin.value.name || '',
    email: admin.value.email || '',
    current_password: '',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.put('/manage/profile', {
        preserveScroll: true,
        onSuccess: () => {
            form.current_password = '';
            form.password = '';
            form.password_confirmation = '';
        },
    });
}
</script>

<template>
    <Head :title="t('profile.title')" />

    <AdminLayout>
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-slate-950">{{ t('profile.title') }}</h1>
        </div>

        <section class="max-w-3xl rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center gap-3 border-b border-slate-200 px-5 py-4">
                <div class="grid h-10 w-10 place-items-center rounded-lg bg-emerald-50 text-emerald-700">
                    <UserRoundCog class="h-5 w-5" />
                </div>
                <div>
                    <h2 class="font-semibold text-slate-950">{{ t('profile.card_title') }}</h2>
                    <p class="text-xs text-slate-500">{{ t('profile.card_subtitle') }}</p>
                </div>
            </div>

            <form class="grid gap-5 p-5" @submit.prevent="submit">
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-slate-600">{{ t('common.name') }}</span>
                    <span class="flex items-center rounded-lg border border-slate-200 px-3 py-2.5 focus-within:border-emerald-500">
                        <UserRoundCog class="me-2 h-5 w-5 text-slate-400" />
                        <input v-model="form.name" class="w-full border-0 bg-transparent text-sm outline-none" type="text" autocomplete="name">
                    </span>
                    <span v-if="form.errors.name" class="mt-1 block text-xs text-red-600">{{ form.errors.name }}</span>
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-slate-600">{{ t('common.email') }}</span>
                    <span class="flex items-center rounded-lg border border-slate-200 px-3 py-2.5 focus-within:border-emerald-500">
                        <Mail class="me-2 h-5 w-5 text-slate-400" />
                        <input v-model="form.email" class="w-full border-0 bg-transparent text-sm outline-none" type="email" autocomplete="email">
                    </span>
                    <span v-if="form.errors.email" class="mt-1 block text-xs text-red-600">{{ form.errors.email }}</span>
                </label>

                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <p class="text-sm font-medium text-slate-800">{{ t('login.password') }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ t('profile.leave_password_blank') }}</p>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <label class="block">
                        <span class="mb-1 block text-xs font-semibold text-slate-600">{{ t('profile.current_password') }}</span>
                        <span class="flex items-center rounded-lg border border-slate-200 px-3 py-2.5 focus-within:border-emerald-500">
                            <LockKeyhole class="me-2 h-5 w-5 text-slate-400" />
                            <input v-model="form.current_password" class="w-full border-0 bg-transparent text-sm outline-none" type="password" autocomplete="current-password">
                        </span>
                        <span v-if="form.errors.current_password" class="mt-1 block text-xs text-red-600">{{ form.errors.current_password }}</span>
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-xs font-semibold text-slate-600">{{ t('profile.new_password') }}</span>
                        <span class="flex items-center rounded-lg border border-slate-200 px-3 py-2.5 focus-within:border-emerald-500">
                            <LockKeyhole class="me-2 h-5 w-5 text-slate-400" />
                            <input v-model="form.password" class="w-full border-0 bg-transparent text-sm outline-none" type="password" autocomplete="new-password">
                        </span>
                        <span v-if="form.errors.password" class="mt-1 block text-xs text-red-600">{{ form.errors.password }}</span>
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-xs font-semibold text-slate-600">{{ t('profile.confirm_password') }}</span>
                        <span class="flex items-center rounded-lg border border-slate-200 px-3 py-2.5 focus-within:border-emerald-500">
                            <LockKeyhole class="me-2 h-5 w-5 text-slate-400" />
                            <input v-model="form.password_confirmation" class="w-full border-0 bg-transparent text-sm outline-none" type="password" autocomplete="new-password">
                        </span>
                    </label>
                </div>

                <div class="flex justify-end border-t border-slate-200 pt-4">
                    <button class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60" :disabled="form.processing">
                        <Save class="h-4 w-4" />
                        {{ t('common.save_changes') }}
                    </button>
                </div>
            </form>
        </section>
    </AdminLayout>
</template>
