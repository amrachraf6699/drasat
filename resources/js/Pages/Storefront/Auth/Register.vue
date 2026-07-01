<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { useStorefrontTranslations } from '@/Composables/useStorefrontTranslations';

const { t } = useStorefrontTranslations();
const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post('/register');
}
</script>

<template>
    <StorefrontLayout>
        <section class="bg-white py-14">
            <div class="mx-auto max-w-md px-4 sm:px-6">
                <h1 class="text-4xl font-semibold text-slate-950">{{ t('auth.register_title') }}</h1>
                <p class="mt-3 text-slate-600">{{ t('auth.register_subtitle') }}</p>

                <form class="mt-8 space-y-4" @submit.prevent="submit">
                    <label class="block">
                        <span class="text-sm font-semibold text-slate-800">{{ t('auth.name') }}</span>
                        <input v-model="form.name" type="text" class="mt-2 h-12 w-full rounded-lg border border-slate-200 px-3 outline-none focus:border-emerald-700">
                        <span v-if="form.errors.name" class="mt-1 block text-sm text-red-600">{{ form.errors.name }}</span>
                    </label>
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
                    <label class="block">
                        <span class="text-sm font-semibold text-slate-800">{{ t('auth.confirm_password') }}</span>
                        <input v-model="form.password_confirmation" type="password" class="mt-2 h-12 w-full rounded-lg border border-slate-200 px-3 outline-none focus:border-emerald-700">
                    </label>
                    <button type="submit" class="h-12 w-full rounded-lg bg-emerald-700 text-sm font-semibold text-white hover:bg-emerald-800" :disabled="form.processing">
                        {{ t('auth.register') }}
                    </button>
                </form>

                <p class="mt-6 text-center text-sm text-slate-600">
                    {{ t('auth.have_account') }}
                    <Link href="/login" class="font-semibold text-emerald-800">{{ t('auth.login') }}</Link>
                </p>
            </div>
        </section>
    </StorefrontLayout>
</template>
