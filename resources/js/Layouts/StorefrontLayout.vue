<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    ChevronDown,
    Globe2,
    Library,
    LogOut,
    Menu,
    ShoppingCart,
    UserRound,
    X,
} from 'lucide-vue-next';
import { useStorefrontTranslations } from '@/Composables/useStorefrontTranslations';

const page = usePage();
const { direction, locale, t } = useStorefrontTranslations();

const mobileOpen = ref(false);
const accountOpen = ref(false);
const toastMessage = ref('');
let toastTimer = null;

const user = computed(() => page.props.auth?.user);
const cartCount = computed(() => page.props.cartSummary?.count || 0);
const flashStatus = computed(() => page.props.flash?.status);
const currentUrl = computed(() => page.url || '/');

const navItems = computed(() => [
    { label: t('layout.studies'), href: '/studies' },
    { label: t('layout.faq'), href: '/#faq' },
    { label: t('layout.library'), href: '/library' },
]);

onMounted(() => {
    document.documentElement.dir = direction.value;
    document.documentElement.lang = locale.value;
});

watch(direction, (value) => {
    document.documentElement.dir = value;
});

watch(locale, (value) => {
    document.documentElement.lang = value;
});

watch(currentUrl, () => {
    mobileOpen.value = false;
    accountOpen.value = false;
});

watch(flashStatus, (message) => {
    if (! message) {
        return;
    }

    toastMessage.value = message;
    clearToastTimer();
    toastTimer = setTimeout(() => {
        toastMessage.value = '';
        toastTimer = null;
    }, 3500);
}, { immediate: true });

onBeforeUnmount(() => clearToastTimer());

function isActive(href) {
    if (href.includes('#')) {
        return false;
    }

    return currentUrl.value === href || currentUrl.value.startsWith(`${href}/`);
}

function switchLocale() {
    router.post(`/locale/${locale.value === 'ar' ? 'en' : 'ar'}`, {}, {
        preserveScroll: true,
    });
}

function logout() {
    router.post('/logout');
}

function clearToastTimer() {
    if (toastTimer) {
        clearTimeout(toastTimer);
        toastTimer = null;
    }
}
</script>

<template>
    <div :dir="direction" class="min-h-screen bg-white text-slate-950">
        <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
            <div class="mx-auto flex h-20 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <Link href="/" class="flex items-center gap-3">
                    <div class="grid h-11 w-11 place-items-center rounded-lg border border-emerald-100 bg-emerald-700 text-xl font-bold text-white shadow-sm">D</div>
                    <div>
                        <p class="text-2xl font-semibold leading-none text-slate-950">{{ t('common.brand') }}</p>
                        <p class="mt-1 hidden text-xs font-medium text-slate-500 sm:block">{{ t('layout.tagline') }}</p>
                    </div>
                </Link>

                <nav class="hidden items-center gap-8 lg:flex">
                    <Link
                        v-for="item in navItems"
                        :key="item.href"
                        :href="item.href"
                        class="relative py-7 text-sm font-semibold text-slate-700 transition hover:text-emerald-800"
                        :class="isActive(item.href) ? 'text-emerald-800' : ''"
                    >
                        {{ item.label }}
                        <span v-if="isActive(item.href)" class="absolute inset-x-0 bottom-0 h-0.5 bg-emerald-700" />
                    </Link>
                </nav>

                <div class="hidden items-center gap-4 lg:flex">
                    <button type="button" class="inline-flex h-10 items-center gap-2 px-2 text-sm font-semibold text-slate-800" @click="switchLocale">
                        <span>{{ t('layout.language') }}</span>
                        <Globe2 class="h-4 w-4" />
                    </button>
                    <div class="h-8 w-px bg-slate-200" />
                    <Link href="/cart" class="relative inline-flex h-11 w-11 items-center justify-center rounded-lg text-slate-900 transition hover:bg-slate-50">
                        <ShoppingCart class="h-6 w-6" />
                        <span v-if="cartCount" class="absolute -end-1 top-1 grid h-5 min-w-5 place-items-center rounded-full bg-emerald-700 px-1 text-xs font-bold text-white">{{ cartCount }}</span>
                    </Link>
                    <div class="h-8 w-px bg-slate-200" />
                    <div v-if="user" class="relative">
                        <button type="button" class="inline-flex h-11 items-center gap-2 rounded-lg px-3 text-sm font-semibold text-slate-800 transition hover:bg-slate-50" @click="accountOpen = !accountOpen">
                            <span class="grid h-8 w-8 place-items-center rounded-full border border-slate-200 bg-slate-50">
                                <UserRound class="h-5 w-5" />
                            </span>
                            <span>{{ user.name }}</span>
                            <ChevronDown class="h-4 w-4" />
                        </button>
                        <div v-if="accountOpen" class="absolute end-0 mt-2 w-56 rounded-lg border border-slate-200 bg-white p-2 shadow-lg">
                            <Link href="/library" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                <Library class="h-4 w-4" />
                                {{ t('layout.library') }}
                            </Link>
                            <button type="button" class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-start text-sm font-medium text-slate-700 hover:bg-slate-50" @click="logout">
                                <LogOut class="h-4 w-4" />
                                {{ t('layout.sign_out') }}
                            </button>
                        </div>
                    </div>
                    <Link v-else href="/login" class="inline-flex h-11 items-center gap-2 rounded-lg bg-emerald-800 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-900">
                        <UserRound class="h-5 w-5" />
                        {{ t('layout.sign_in') }}
                    </Link>
                </div>

                <div class="flex items-center gap-2 lg:hidden">
                    <button type="button" class="grid h-10 w-10 place-items-center rounded-lg border border-slate-200" @click="switchLocale">
                        <Globe2 class="h-5 w-5" />
                    </button>
                    <Link href="/cart" class="relative grid h-10 w-10 place-items-center rounded-lg border border-slate-200">
                        <ShoppingCart class="h-5 w-5" />
                        <span v-if="cartCount" class="absolute -end-1 -top-1 grid h-5 min-w-5 place-items-center rounded-full bg-emerald-700 px-1 text-xs font-bold text-white">{{ cartCount }}</span>
                    </Link>
                    <button type="button" class="grid h-10 w-10 place-items-center rounded-lg border border-slate-200" @click="mobileOpen = true">
                        <Menu class="h-5 w-5" />
                    </button>
                </div>
            </div>
        </header>

        <div v-if="mobileOpen" class="fixed inset-0 z-50 bg-slate-950/30 lg:hidden" @click="mobileOpen = false" />
        <aside
            class="fixed inset-y-0 end-0 z-[60] w-80 max-w-[88vw] border-s border-slate-200 bg-white p-5 shadow-xl transition lg:hidden"
            :class="mobileOpen ? 'translate-x-0' : 'translate-x-full rtl:-translate-x-full'"
        >
            <div class="mb-6 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="grid h-10 w-10 place-items-center rounded-lg bg-emerald-700 text-lg font-bold text-white">D</div>
                    <p class="text-xl font-semibold">{{ t('common.brand') }}</p>
                </div>
                <button type="button" class="grid h-10 w-10 place-items-center rounded-lg border border-slate-200" @click="mobileOpen = false">
                    <X class="h-5 w-5" />
                </button>
            </div>
            <nav class="space-y-2">
                <Link v-for="item in navItems" :key="item.href" :href="item.href" class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    <BookOpen class="h-5 w-5 text-emerald-700" />
                    {{ item.label }}
                </Link>
            </nav>
            <div class="mt-6 border-t border-slate-200 pt-5">
                <Link v-if="!user" href="/login" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-emerald-800 px-5 text-sm font-semibold text-white">
                    <UserRound class="h-5 w-5" />
                    {{ t('layout.sign_in') }}
                </Link>
                <div v-else class="space-y-3">
                    <div class="rounded-lg bg-slate-50 p-4">
                        <p class="font-semibold">{{ user.name }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ user.email }}</p>
                    </div>
                    <button type="button" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg border border-slate-300 px-4 text-sm font-semibold" @click="logout">
                        <LogOut class="h-5 w-5" />
                        {{ t('layout.sign_out') }}
                    </button>
                </div>
            </div>
        </aside>

        <main>
            <slot />
        </main>

        <footer class="border-t border-slate-200 bg-white">
            <div class="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-8 text-sm text-slate-500 sm:px-6 md:flex-row md:items-center md:justify-between lg:px-8">
                <p>{{ t('common.brand') }} · {{ t('layout.tagline') }}</p>
                <div class="flex items-center gap-4">
                    <Link href="/studies" class="hover:text-emerald-800">{{ t('layout.studies') }}</Link>
                    <Link href="/#faq" class="hover:text-emerald-800">{{ t('layout.faq') }}</Link>
                    <Link href="/library" class="hover:text-emerald-800">{{ t('layout.library') }}</Link>
                </div>
            </div>
        </footer>

        <div v-if="toastMessage" class="fixed bottom-5 start-1/2 z-[70] w-[calc(100%-2rem)] max-w-md -translate-x-1/2 rounded-lg border border-emerald-200 bg-white px-4 py-3 text-sm font-semibold text-emerald-900 shadow-lg rtl:translate-x-1/2">
            {{ toastMessage }}
        </div>
    </div>
</template>
