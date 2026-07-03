<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { useStorefrontTranslations } from '@/Composables/useStorefrontTranslations';

const { t } = useStorefrontTranslations();
const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post('/login');
}
</script>

<template>
    <StorefrontLayout>
        <section class="bg-white py-14">
            <div class="mx-auto max-w-md px-4 sm:px-6">
                <h1 class="text-4xl font-semibold text-slate-950">{{ t('auth.login_title') }}</h1>
                <p class="mt-3 text-slate-600">{{ t('auth.login_subtitle') }}</p>

                <form class="mt-8 space-y-4" @submit.prevent="submit">
                    <label class="block">
                        <span class="text-sm font-semibold text-slate-800">{{ t('auth.email') }}</span>
                        <input v-model="form.email" type="email" class="mt-2 h-12 w-full rounded-lg border border-slate-200 px-3 outline-none focus:border-emerald-700">
                        <span v-if="form.errors.email" class="mt-1 block text-sm text-red-600">{{ form.errors.email }}</span>
                    </label>
                    <label class="block">
                        <span class="text-sm font-semibold text-slate-800">{{ t('auth.password') }}</span>
                        <input v-model="form.password" type="password" class="mt-2 h-12 w-full rounded-lg border border-slate-200 px-3 outline-none focus:border-emerald-700">
                        <span v-if="form.errors.password" class="mt-1 block text-sm text-red-600">{{ form.errors.password }}</span>
                    </label>
                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input v-model="form.remember" type="checkbox" class="rounded border-slate-300 text-emerald-700">
                        {{ t('auth.remember') }}
                    </label>
                    <button type="submit" class="h-12 w-full rounded-lg bg-emerald-700 text-sm font-semibold text-white hover:bg-emerald-800" :disabled="form.processing">
                        {{ t('auth.login') }}
                    </button>
                    <div class="pt-2">
                        <div class="flex items-center gap-3">
                            <span class="h-px flex-1 bg-slate-200"></span>
                            <span class="text-xs font-semibold uppercase text-slate-400">{{ t('auth.or_continue_with') }}</span>
                            <span class="h-px flex-1 bg-slate-200"></span>
                        </div>
                        <div class="mt-4 flex items-center justify-center gap-3">
                            <a href="/auth/google/redirect" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-300 bg-white text-slate-700 transition hover:border-emerald-700 hover:bg-slate-50" :aria-label="t('auth.google')" :title="t('auth.google')">
                                <svg class="h-5 w-5" viewBox="0 0 533.5 544.3" aria-hidden="true" focusable="false">
                                    <path fill="#4285f4" d="M533.5 278.4c0-18.5-1.5-37.1-4.7-55.3H272.1v104.8h147c-6.1 33.8-25.7 63.7-54.4 82.7v68h88.2c51.6-47.5 80.6-117.7 80.6-200.2z" />
                                    <path fill="#34a853" d="M272.1 544.3c73.6 0 135.6-24.1 180.8-65.7l-88.2-68.5c-24.5 16.7-56.2 26.2-92.6 26.2-71 0-131.2-47.9-152.8-112.3H28.4v70.5c46.4 92.3 140.9 149.8 243.7 149.8z" />
                                    <path fill="#fbbc04" d="M119.3 324c-11.4-33.8-11.4-70.4 0-104.2v-70.5H28.4c-38.8 77.3-38.8 168.4 0 245.7l90.9-71z" />
                                    <path fill="#ea4335" d="M272.1 107.7c38.9-.6 76.3 14 104.4 40.9l77.8-77.8C405 24.6 339.7-.8 272.1 0 169.3 0 74.8 57.5 28.4 149.3l90.9 70.5c21.5-64.5 81.8-112.1 152.8-112.1z" />
                                </svg>
                            </a>
                            <a href="/auth/facebook/redirect" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-300 bg-white text-[#1877f2] transition hover:border-emerald-700 hover:bg-slate-50" :aria-label="t('auth.facebook')" :title="t('auth.facebook')">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                    <path fill="currentColor" d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.438H7.078v-3.49h3.047V9.413c0-3.025 1.792-4.697 4.533-4.697 1.312 0 2.686.236 2.686.236v2.971H15.83c-1.491 0-1.956.931-1.956 1.887v2.263h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </form>

                <p class="mt-6 text-center text-sm text-slate-600">
                    {{ t('auth.need_account') }}
                    <Link href="/register" class="font-semibold text-emerald-800">{{ t('auth.register') }}</Link>
                </p>
            </div>
        </section>
    </StorefrontLayout>
</template>
