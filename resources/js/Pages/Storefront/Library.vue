<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { CheckCircle2, Clock3, Download, FileText, LogOut, UserRound } from 'lucide-vue-next';
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import StudyCover from '@/Components/Storefront/StudyCover.vue';
import { useStorefrontTranslations } from '@/Composables/useStorefrontTranslations';

defineProps({
    purchases: {
        type: Array,
        default: () => [],
    },
    pendingOrders: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const { t } = useStorefrontTranslations();
const user = page.props.auth?.user;

function logout() {
    router.post('/logout');
}
</script>

<template>
    <StorefrontLayout>
        <section class="bg-white py-12">
            <div class="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 lg:grid-cols-[280px_1fr] lg:px-8">
                <aside class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm lg:self-start">
                    <div class="py-6 text-center">
                        <div class="mx-auto grid h-20 w-20 place-items-center rounded-full border border-slate-200 bg-slate-50">
                            <UserRound class="h-10 w-10 text-slate-900" />
                        </div>
                        <p class="mt-4 text-xl font-semibold text-slate-950">{{ user?.name }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ user?.email }}</p>
                    </div>
                    <nav class="mt-4 space-y-2 border-t border-slate-200 pt-4">
                        <Link href="/library" class="flex items-center gap-3 rounded-lg bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                            <FileText class="h-5 w-5" />
                            {{ t('layout.orders') }}
                        </Link>
                        <button type="button" class="flex w-full items-center gap-3 rounded-lg px-4 py-3 text-start text-sm font-semibold text-slate-700 hover:bg-slate-50" @click="logout">
                            <LogOut class="h-5 w-5" />
                            {{ t('layout.sign_out') }}
                        </button>
                    </nav>
                </aside>

                <div>
                    <h1 class="text-4xl font-semibold text-slate-950">{{ t('library.title') }}</h1>
                    <p class="mt-3 text-lg text-slate-600">{{ t('library.subtitle') }}</p>

                    <div v-if="purchases.length || pendingOrders.length" class="mt-8 space-y-5">
                        <article v-for="purchase in purchases" :key="`purchase-${purchase.id}`" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                            <div class="grid gap-5 lg:grid-cols-[150px_1fr_auto]">
                                <StudyCover v-if="purchase.product" :product="purchase.product" compact />
                                <div>
                                    <p class="text-2xl font-semibold text-slate-950">{{ purchase.product?.title }}</p>
                                    <p class="mt-2 text-slate-500">{{ t('library.order', { number: purchase.order_number || purchase.id }) }}</p>
                                    <p class="mt-1 text-slate-500">{{ purchase.purchased_at }}</p>
                                </div>
                                <div class="flex items-center gap-2 self-start text-emerald-700">
                                    <CheckCircle2 class="h-6 w-6" />
                                    <div>
                                        <p class="font-semibold">{{ t('library.purchased') }}</p>
                                        <p class="mt-1 text-sm text-slate-500">{{ t('library.payment_completed') }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 overflow-hidden rounded-lg border border-slate-200">
                                <div class="grid grid-cols-[1fr_120px_150px] bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700">
                                    <span>{{ t('library.file_name') }}</span>
                                    <span>{{ t('library.size') }}</span>
                                    <span class="text-end">{{ t('library.action') }}</span>
                                </div>
                                <div v-for="document in purchase.documents" :key="document.id" class="grid grid-cols-[1fr_120px_150px] items-center border-t border-slate-200 px-4 py-3 text-sm">
                                    <span class="flex min-w-0 items-center gap-3">
                                        <FileText class="h-5 w-5 shrink-0 text-emerald-700" />
                                        <span class="truncate">{{ document.name }}</span>
                                    </span>
                                    <span class="text-slate-500">{{ document.size }}</span>
                                    <a :href="document.download_url" class="inline-flex h-9 items-center justify-center gap-2 rounded-lg border border-emerald-700 px-3 text-sm font-semibold text-emerald-800 hover:bg-emerald-50">
                                        <Download class="h-4 w-4" />
                                        {{ t('common.download') }}
                                    </a>
                                </div>
                            </div>
                        </article>

                        <article v-for="order in pendingOrders" :key="`order-${order.id}`" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                            <div class="grid gap-5 lg:grid-cols-[150px_1fr_auto]">
                                <StudyCover v-if="order.product" :product="order.product" compact />
                                <div>
                                    <p class="text-2xl font-semibold text-slate-950">{{ order.product?.title || order.items?.[0]?.title }}</p>
                                    <p class="mt-2 text-slate-500">{{ t('library.order', { number: order.order_number }) }}</p>
                                    <p class="mt-1 text-slate-500">{{ order.created_at }}</p>
                                </div>
                                <div class="flex items-center gap-2 self-start text-amber-600">
                                    <Clock3 class="h-6 w-6" />
                                    <div>
                                        <p class="font-semibold">{{ order.status_label }}</p>
                                        <p class="mt-1 text-sm text-slate-500">{{ order.transfer?.status_label || t('library.payment_pending') }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 grid gap-4 rounded-lg border border-slate-200 p-4 text-sm md:grid-cols-2">
                                <div>
                                    <p class="font-semibold text-slate-950">{{ t('library.payment_pending') }}</p>
                                    <p class="mt-2 leading-6 text-slate-600">{{ order.transfer?.status_label || order.status_label }}</p>
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-950">{{ t('library.proof') }}</p>
                                    <p class="mt-2 text-slate-600">{{ order.transfer?.proof_name || t('common.empty') }}</p>
                                    <p v-if="order.transfer?.reference_number" class="mt-2 text-slate-600">{{ t('library.reference') }}: {{ order.transfer.reference_number }}</p>
                                </div>
                            </div>

                            <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">
                                {{ t('library.locked') }}
                            </div>
                        </article>
                    </div>

                    <div v-else class="mt-8 rounded-lg border border-dashed border-slate-300 p-12 text-center">
                        <p class="text-lg font-semibold text-slate-950">{{ t('library.empty') }}</p>
                        <Link href="/studies" class="mt-5 inline-flex h-11 items-center rounded-lg bg-emerald-700 px-5 text-sm font-semibold text-white">{{ t('home.browse') }}</Link>
                    </div>

                    <p class="mt-6 text-sm text-slate-500">{{ t('library.help', { email: 'support@drasa.test' }) }}</p>
                </div>
            </div>
        </section>
    </StorefrontLayout>
</template>
