<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';
import { LockKeyhole, Mail, ShieldCheck } from 'lucide-vue-next';

const { direction, t } = useTranslations();

const form = useForm({
    email: 'admin@drasa.test',
    password: 'password',
    remember: true,
});

function submit() {
    form.post('/manage/login', {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="t('login.title')" />

    <main :dir="direction" class="grid min-h-screen bg-slate-50 lg:grid-cols-[1fr_520px]">
        <section class="hidden border-e border-slate-200 bg-white px-12 py-10 lg:flex lg:flex-col">
            <div class="flex items-center gap-3">
                <div class="grid h-12 w-12 place-items-center rounded-lg bg-emerald-50 text-emerald-700">
                    <ShieldCheck class="h-7 w-7" />
                </div>
                <div>
                    <p class="text-xl font-semibold">{{ t('login.brand_ar') }}</p>
                    <p class="text-sm text-slate-500">{{ t('login.brand_subtitle') }}</p>
                </div>
            </div>

            <div class="my-auto max-w-xl">
                <p class="mb-4 text-sm font-semibold uppercase tracking-[0.22em] text-emerald-700">{{ t('login.workspace') }}</p>
                <h1 class="text-5xl font-semibold leading-tight text-slate-950">{{ t('login.headline') }}</h1>
                <p class="mt-5 text-lg leading-8 text-slate-500">
                    {{ t('login.description') }}
                </p>
            </div>
        </section>

        <section class="flex items-center justify-center px-5 py-10">
            <div class="w-full max-w-md rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-8 text-center">
                    <div class="mx-auto mb-4 grid h-14 w-14 place-items-center rounded-lg bg-emerald-50 text-emerald-700">
                        <ShieldCheck class="h-8 w-8" />
                    </div>
                    <h1 class="text-2xl font-semibold text-slate-950">{{ t('login.form_title') }}</h1>
                    <p class="mt-2 text-sm text-slate-500">{{ t('login.form_subtitle') }}</p>
                </div>

                <form class="space-y-5" @submit.prevent="submit">
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">{{ t('common.email') }}</span>
                        <span class="flex items-center rounded-lg border border-slate-200 px-3 py-3 focus-within:border-emerald-500 focus-within:ring-2 focus-within:ring-emerald-100">
                            <Mail class="me-2 h-5 w-5 text-slate-400" />
                            <input v-model="form.email" class="w-full border-0 bg-transparent text-sm outline-none" type="email" autocomplete="email">
                        </span>
                        <span v-if="form.errors.email" class="mt-1 block text-xs text-red-600">{{ form.errors.email }}</span>
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">{{ t('login.password') }}</span>
                        <span class="flex items-center rounded-lg border border-slate-200 px-3 py-3 focus-within:border-emerald-500 focus-within:ring-2 focus-within:ring-emerald-100">
                            <LockKeyhole class="me-2 h-5 w-5 text-slate-400" />
                            <input v-model="form.password" class="w-full border-0 bg-transparent text-sm outline-none" type="password" autocomplete="current-password">
                        </span>
                        <span v-if="form.errors.password" class="mt-1 block text-xs text-red-600">{{ form.errors.password }}</span>
                    </label>

                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input v-model="form.remember" class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" type="checkbox">
                        {{ t('login.remember') }}
                    </label>

                    <button
                        type="submit"
                        class="w-full rounded-lg bg-emerald-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="form.processing"
                    >
                        {{ t('login.sign_in') }}
                    </button>
                </form>
            </div>
        </section>
    </main>
</template>
