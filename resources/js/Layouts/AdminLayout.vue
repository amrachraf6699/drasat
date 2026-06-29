<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    Bell,
    ChevronDown,
    ChevronsLeft,
    ChevronsRight,
    CheckCircle2,
    CircleHelp,
    Globe2,
    Home,
    Landmark,
    KeyRound,
    LayoutGrid,
    LogOut,
    Menu,
    Package,
    Plus,
    Search,
    Settings,
    ShieldCheck,
    ShoppingCart,
    UserRoundCog,
    UsersRound,
    X,
} from 'lucide-vue-next';
import { useTranslations } from '@/Composables/useTranslations';

const page = usePage();
const { direction, locale, t } = useTranslations();

const drawerOpen = ref(false);
const collapsed = ref(false);
const profileOpen = ref(false);
const notificationsOpen = ref(false);
const searchQuery = ref('');
const toastMessage = ref('');
let toastTimer = null;

const admin = computed(() => page.props.auth?.admin);
const currentUrl = computed(() => page.url || '/manage');
const flashStatus = computed(() => page.props.flash?.status);
const adminNotifications = computed(() => page.props.adminNotifications || {});
const notificationItems = computed(() => adminNotifications.value.items || []);
const pendingTransfers = computed(() => adminNotifications.value.pendingTransfers || []);
const notificationCount = computed(() => {
    return (adminNotifications.value.unreadCount || 0) + (adminNotifications.value.pendingTransferCount || 0);
});
const adminPermissions = computed(() => admin.value?.permissions || []);

const allNavItems = computed(() => [
    { label: t('layout.nav.dashboard'), subLabel: t('layout.nav_sub.overview'), href: '/manage', icon: Home, exact: true },
    { label: t('layout.nav.products'), subLabel: t('layout.nav_sub.studies'), href: '/manage/products', icon: Package, permissions: ['products.view'] },
    { label: t('layout.nav.orders'), subLabel: t('layout.nav_sub.purchases'), href: '/manage/orders', icon: ShoppingCart, permissions: ['orders.view'] },
    { label: t('layout.nav.bank_transfers'), subLabel: t('layout.nav_sub.approvals'), href: '/manage/bank-transfers', icon: Landmark, permissions: ['transfers.view', 'transfers.review'] },
    { label: t('layout.nav.users'), subLabel: t('layout.nav_sub.customers'), href: '/manage/users', icon: UsersRound, permissions: ['users.view'] },
    { label: t('layout.nav.admins'), subLabel: t('layout.nav_sub.team'), href: '/manage/admins', icon: ShieldCheck, permissions: ['admins.view'] },
    { label: t('layout.nav.roles'), subLabel: t('layout.nav_sub.permissions'), href: '/manage/roles', icon: KeyRound, permissions: ['roles.view', 'roles.manage'] },
    { label: t('layout.nav.faqs'), subLabel: t('layout.nav_sub.bilingual'), href: '/manage/faqs', icon: CircleHelp, permissions: ['faqs.view', 'faqs.manage'] },
    { label: t('layout.nav.settings'), subLabel: t('layout.nav_sub.site_data'), href: '/manage/settings', icon: Settings, permissions: ['settings.view', 'settings.manage'] },
]);

const navItems = computed(() => allNavItems.value.filter((item) => canAccess(item)));

const allBottomItems = computed(() => [
    { label: t('layout.bottom.home'), href: '/manage', icon: Home, exact: true },
    { label: t('layout.bottom.orders'), href: '/manage/orders', icon: ShoppingCart, permissions: ['orders.view'] },
    { label: t('layout.bottom.add'), href: '/manage/products', icon: Plus, elevated: true, permissions: ['products.view'] },
    { label: t('layout.bottom.transfers'), href: '/manage/bank-transfers', icon: Landmark, permissions: ['transfers.view', 'transfers.review'] },
    { label: t('layout.bottom.settings'), href: '/manage/settings', icon: Settings, permissions: ['settings.view', 'settings.manage'] },
]);

const bottomItems = computed(() => allBottomItems.value.filter((item) => canAccess(item)));

onMounted(() => {
    collapsed.value = localStorage.getItem('admin-sidebar-collapsed') === 'true';
    document.documentElement.dir = direction.value;
    document.documentElement.lang = locale.value;
});

watch(collapsed, (value) => {
    localStorage.setItem('admin-sidebar-collapsed', String(value));
});

watch(direction, (value) => {
    document.documentElement.dir = value;
});

watch(locale, (value) => {
    document.documentElement.lang = value;
});

watch(currentUrl, () => {
    drawerOpen.value = false;
    notificationsOpen.value = false;
    profileOpen.value = false;
});

watch(flashStatus, (message) => {
    if (!message) {
        return;
    }

    showToast(message);
}, { immediate: true });

onBeforeUnmount(() => {
    clearToastTimer();
});

function isActive(item) {
    return item.exact ? currentUrl.value === item.href : currentUrl.value.startsWith(item.href);
}

function canAccess(item) {
    if (!item.permissions?.length) {
        return true;
    }

    return item.permissions.some((permission) => adminPermissions.value.includes(permission));
}

function closeDrawer() {
    drawerOpen.value = false;
}

function switchLocale() {
    const nextLocale = locale.value === 'ar' ? 'en' : 'ar';
    router.post(`/manage/locale/${nextLocale}`, {}, {
        preserveScroll: true,
    });
}

function runSearch() {
    const term = searchQuery.value.trim().toLowerCase();

    if (!term) {
        return;
    }

    const target = navItems.value.find((item) => {
        return item.label.toLowerCase().includes(term) || item.subLabel.toLowerCase().includes(term);
    });

    router.visit(target?.href || '/manage/products');
}

function markNotificationsRead() {
    router.post('/manage/notifications/read', {}, {
        preserveScroll: true,
        onSuccess: () => {
            notificationsOpen.value = false;
        },
    });
}

function logout() {
    router.post('/manage/logout');
}

function clearToastTimer() {
    if (toastTimer) {
        clearTimeout(toastTimer);
        toastTimer = null;
    }
}

function showToast(message) {
    toastMessage.value = message;
    clearToastTimer();
    toastTimer = setTimeout(() => {
        toastMessage.value = '';
        toastTimer = null;
    }, 3500);
}

function closeToast() {
    toastMessage.value = '';
    clearToastTimer();
}
</script>

<template>
    <div :dir="direction" class="min-h-screen overflow-x-hidden bg-slate-50 text-slate-950">
        <aside
            class="fixed inset-y-0 start-0 z-30 hidden border-e border-slate-200 bg-white/95 transition-[width] duration-200 lg:block"
            :class="collapsed ? 'w-20' : 'w-72'"
        >
            <div class="flex h-full flex-col px-4 py-6">
                <Link href="/manage" class="mb-8 flex items-center gap-3" :class="collapsed ? 'justify-center' : ''">
                    <div class="grid h-11 w-11 shrink-0 place-items-center rounded-lg bg-emerald-50 text-emerald-700">
                        <LayoutGrid class="h-7 w-7" stroke-width="2.4" />
                    </div>
                    <div v-if="!collapsed" class="min-w-0">
                        <p class="truncate text-lg font-semibold leading-tight">{{ t('layout.brand') }}</p>
                        <p class="truncate text-xs font-medium text-slate-500">{{ t('layout.admin_dashboard') }}</p>
                    </div>
                </Link>

                <nav class="space-y-2">
                    <Link
                        v-for="item in navItems"
                        :key="item.href"
                        :href="item.href"
                        :title="collapsed ? item.label : null"
                        class="group flex items-center rounded-lg text-sm transition"
                        :class="[
                            collapsed ? 'justify-center px-0 py-3' : 'gap-3 px-4 py-3',
                            isActive(item) ? 'bg-emerald-50 text-emerald-800' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950'
                        ]"
                    >
                        <component :is="item.icon" class="h-5 w-5 shrink-0" stroke-width="1.9" />
                        <span v-if="!collapsed" class="min-w-0">
                            <span class="block truncate font-medium">{{ item.label }}</span>
                        </span>
                    </Link>
                </nav>

                <button
                    type="button"
                    class="mt-auto flex items-center rounded-lg text-start text-sm text-slate-500 transition hover:bg-slate-50 hover:text-slate-950"
                    :class="collapsed ? 'justify-center px-0 py-3' : 'gap-3 px-4 py-3'"
                    :title="collapsed ? t('layout.expand_sidebar') : t('layout.collapse')"
                    @click="collapsed = !collapsed"
                >
                    <ChevronsRight v-if="collapsed" class="h-5 w-5" />
                    <ChevronsLeft v-else class="h-5 w-5" />
                    <span v-if="!collapsed" class="min-w-0">
                        <span class="block truncate font-medium">{{ t('layout.collapse') }}</span>
                    </span>
                </button>
            </div>
        </aside>

        <div
            v-if="drawerOpen"
            class="fixed inset-0 z-40 bg-slate-950/30 lg:hidden"
            @click="closeDrawer"
        />

        <aside
            class="fixed inset-y-0 start-0 z-50 w-72 border-e border-slate-200 bg-white p-5 transition lg:hidden"
            :class="drawerOpen ? 'translate-x-0' : '-translate-x-full rtl:translate-x-full'"
        >
            <div class="mb-7 flex items-center justify-between">
                <Link href="/manage" class="flex items-center gap-3" @click="closeDrawer">
                    <div class="grid h-10 w-10 place-items-center rounded-lg bg-emerald-50 text-emerald-700">
                        <LayoutGrid class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="font-semibold">{{ t('layout.brand') }}</p>
                        <p class="text-xs text-slate-500">{{ t('layout.admin_dashboard') }}</p>
                    </div>
                </Link>
                <button type="button" class="rounded-lg border border-slate-200 p-2" @click="closeDrawer">
                    <X class="h-5 w-5" />
                </button>
            </div>
            <nav class="space-y-2">
                <Link
                    v-for="item in navItems"
                    :key="item.href"
                    :href="item.href"
                    class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm"
                    :class="isActive(item) ? 'bg-emerald-50 text-emerald-800' : 'text-slate-600'"
                    @click="closeDrawer"
                >
                    <component :is="item.icon" class="h-5 w-5" />
                    <span>
                        <span class="block font-medium">{{ item.label }}</span>
                    </span>
                </Link>
            </nav>
        </aside>

        <div class="min-w-0 transition-[padding] duration-200" :class="collapsed ? 'lg:ps-20' : 'lg:ps-72'">
            <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/95 backdrop-blur">
                <div class="flex h-20 min-w-0 items-center gap-4 px-4 sm:px-6 lg:px-8">
                    <button
                        type="button"
                        class="rounded-lg border border-slate-200 p-2 text-slate-700 lg:hidden"
                        @click="drawerOpen = true"
                    >
                        <Menu class="h-5 w-5" />
                    </button>

                    <form class="hidden min-w-0 flex-1 items-center rounded-lg border border-slate-200 bg-white px-4 py-2.5 shadow-sm sm:flex lg:max-w-md" @submit.prevent="runSearch">
                        <Search class="me-3 h-5 w-5 shrink-0 text-slate-400" />
                        <input v-model="searchQuery" class="w-full min-w-0 border-0 bg-transparent text-sm outline-none placeholder:text-slate-400" :placeholder="t('layout.search_placeholder')" type="search">
                    </form>

                    <div class="ms-auto flex shrink-0 items-center">
                        <button
                            type="button"
                            class="grid h-10 w-10 place-items-center rounded-lg text-slate-600 hover:bg-slate-50"
                            :title="t('layout.switch_language')"
                            @click="switchLocale"
                        >
                            <Globe2 class="h-5 w-5" />
                        </button>

                        <div class="relative">
                            <button
                                type="button"
                                class="relative grid h-10 w-10 place-items-center rounded-lg text-slate-600 hover:bg-slate-50"
                                :title="t('layout.notifications')"
                                @click="notificationsOpen = !notificationsOpen"
                            >
                                <Bell class="h-5 w-5" />
                                <span v-if="notificationCount" class="absolute end-1.5 top-1.5 grid h-4 min-w-4 place-items-center rounded-full bg-emerald-600 px-1 text-[10px] font-semibold text-white">
                                    {{ notificationCount > 9 ? '9+' : notificationCount }}
                                </span>
                            </button>

                            <div v-if="notificationsOpen" class="absolute end-0 top-12 z-30 w-[min(22rem,calc(100vw-2rem))] rounded-lg border border-slate-200 bg-white text-sm shadow-xl">
                                <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
                                    <p class="font-semibold text-slate-950">{{ t('layout.notifications') }}</p>
                                    <button v-if="notificationItems.length" type="button" class="text-xs font-semibold text-emerald-700 hover:text-emerald-800" @click="markNotificationsRead">
                                        {{ t('layout.mark_all_read') }}
                                    </button>
                                </div>

                                <div class="max-h-96 overflow-y-auto">
                                    <Link
                                        v-for="notification in notificationItems"
                                        :key="notification.id"
                                        :href="notification.href || '/manage'"
                                        class="block border-b border-slate-100 px-4 py-3 hover:bg-slate-50"
                                    >
                                        <p class="font-medium text-slate-900">{{ notification.title }}</p>
                                        <p v-if="notification.body" class="mt-1 text-xs leading-5 text-slate-500">{{ notification.body }}</p>
                                        <p v-if="notification.created_at" class="mt-2 text-[11px] text-slate-400">{{ notification.created_at }}</p>
                                    </Link>

                                    <Link
                                        v-for="transfer in pendingTransfers"
                                        :key="`transfer-${transfer.id}`"
                                        :href="transfer.href"
                                        class="block border-b border-slate-100 px-4 py-3 hover:bg-slate-50"
                                    >
                                        <p class="font-medium text-slate-900">{{ transfer.title }}</p>
                                        <p class="mt-1 text-xs leading-5 text-slate-500">{{ transfer.body }}</p>
                                        <p v-if="transfer.created_at" class="mt-2 text-[11px] text-slate-400">{{ transfer.created_at }}</p>
                                    </Link>

                                    <div v-if="notificationItems.length === 0 && pendingTransfers.length === 0" class="px-4 py-8 text-center text-sm text-slate-500">
                                        {{ t('layout.no_notifications') }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="relative flex items-center ps-2">
                            <button class="flex items-center gap-2 rounded-full border border-slate-200 px-2 py-1.5 hover:bg-slate-50" type="button" @click="profileOpen = !profileOpen">
                                <span class="grid h-9 w-9 place-items-center rounded-full bg-slate-100 text-sm font-semibold">
                                    {{ (admin?.name || 'A').charAt(0) }}
                                </span>
                                <ChevronDown class="h-4 w-4 text-slate-400" />
                            </button>

                            <div v-if="profileOpen" class="absolute end-0 top-12 z-30 w-56 rounded-2xl border border-slate-200 bg-white p-2 text-sm shadow-xl mt-1">
                                <Link href="/manage/profile" class="flex items-center gap-2 rounded-md px-3 py-2 text-slate-600 hover:bg-slate-50 hover:text-slate-950">
                                    <UserRoundCog class="h-4 w-4" />
                                    {{ t('layout.edit_profile') }}
                                </Link>
                                <button type="button" class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-start text-red-600 hover:bg-red-50" @click="logout">
                                    <LogOut class="h-4 w-4" />
                                    {{ t('layout.logout') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="min-w-0 max-w-full overflow-x-hidden px-4 pb-28 pt-6 sm:px-6 lg:px-8 lg:pb-8">
                <slot />
            </main>
        </div>

        <nav class="fixed inset-x-0 bottom-[-10px] z-30 border-t border-slate-200 bg-white px-3 pb-4 pt-2 shadow-[0_-10px_30px_rgba(15,23,42,0.08)] lg:hidden rounded-full">
            <div class="grid items-end gap-1" :style="{ gridTemplateColumns: `repeat(${bottomItems.length}, minmax(0, 1fr))` }">
                <Link
                    v-for="item in bottomItems"
                    :key="item.href"
                    :href="item.href"
                    class="flex flex-col items-center gap-1 rounded-lg px-2 py-1.5 text-[11px]"
                    :class="isActive(item) ? 'text-emerald-700' : 'text-slate-500'"
                >
                    <span
                        class="grid place-items-center"
                        :class="item.elevated ? '-mt-16 h-12 w-12 rounded-full bg-emerald-600 text-white shadow-lg shadow-emerald-600/30' : 'h-6 w-6'"
                    >
                        <component :is="item.icon" class="h-5 w-5" />
                    </span>
                    <span class="font-medium">{{ item.label }}</span>
                </Link>
            </div>
        </nav>

        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="translate-y-3 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-3 opacity-0"
        >
            <div
                v-if="toastMessage"
                class="fixed top-24 start-4 end-4 z-50 mx-auto flex max-w-md items-start gap-3 rounded-lg border border-emerald-200 bg-white p-4 text-sm text-slate-700 shadow-xl shadow-slate-950/10 lg:top-21 lg:end-6 lg:start-auto lg:mx-0"
                role="status"
                aria-live="polite"
            >
                <CheckCircle2 class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" />
                <p class="min-w-0 flex-1 font-medium leading-5">{{ toastMessage }}</p>
                <button
                    type="button"
                    class="grid h-6 w-6 shrink-0 place-items-center rounded-md text-slate-400 transition hover:bg-slate-50 hover:text-slate-700"
                    :title="t('common.cancel')"
                    @click="closeToast"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>
        </Transition>
    </div>
</template>
