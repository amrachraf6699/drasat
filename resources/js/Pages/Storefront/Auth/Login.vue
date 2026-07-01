<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { Globe2, Mail } from 'lucide-vue-next';
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

                <div class="mt-8 grid gap-3">
                    <a href="/auth/google/redirect" class="inline-flex h-12 items-center justify-center gap-2 rounded-lg border border-slate-300 text-sm font-semibold text-slate-800 hover:border-emerald-700">
                        <Mail class="h-5 w-5" />
                        {{ t('auth.google') }}
                    </a>
                    <a href="/auth/facebook/redirect" class="inline-flex h-12 items-center justify-center gap-2 rounded-lg border border-slate-300 text-sm font-semibold text-slate-800 hover:border-emerald-700">
                        <Globe2 class="h-5 w-5" />
                        {{ t('auth.facebook') }}
                    </a>
                </div>

                <form class="mt-6 space-y-4" @submit.prevent="submit">
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
                </form>

                <p class="mt-6 text-center text-sm text-slate-600">
                    {{ t('auth.need_account') }}
                    <Link href="/register" class="font-semibold text-emerald-800">{{ t('auth.register') }}</Link>
                </p>
            </div>
        </section>
    </StorefrontLayout>
</template>
