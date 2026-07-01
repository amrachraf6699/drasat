<script setup>
import { nextTick, onMounted, ref, watch } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { CreditCard, Landmark, LockKeyhole, UploadCloud } from 'lucide-vue-next';
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import StudyCover from '@/Components/Storefront/StudyCover.vue';
import CartSummary from '@/Components/Storefront/CartSummary.vue';
import { useStorefrontTranslations } from '@/Composables/useStorefrontTranslations';

const props = defineProps({
    cart: {
        type: Object,
        required: true,
    },
    paypal: {
        type: Object,
        required: true,
    },
});

const { t } = useStorefrontTranslations();
const method = ref('paypal');
const paypalButtons = ref(null);
const paypalError = ref('');
const paypalLoading = ref(false);
let paypalPaymentId = null;

const form = useForm({
    reference_number: '',
    proof: null,
});

onMounted(() => {
    if (props.paypal.enabled) {
        loadPayPalButtons();
    } else {
        method.value = 'bank_transfer';
    }
});

watch(method, () => {
    if (method.value === 'paypal' && props.paypal.enabled) {
        nextTick(() => loadPayPalButtons());
    }
});

function submitTransfer() {
    form.post('/checkout/bank-transfer', {
        forceFormData: true,
    });
}

function setProof(event) {
    form.proof = event.target.files?.[0] || null;
}

function loadPayPalButtons() {
    if (! props.paypal.enabled || ! paypalButtons.value) {
        return;
    }

    paypalButtons.value.innerHTML = '';
    paypalError.value = '';

    const existingScript = document.getElementById('paypal-sdk');

    if (existingScript) {
        renderPayPalButtons();
        return;
    }

    const script = document.createElement('script');
    script.id = 'paypal-sdk';
    script.src = `https://www.paypal.com/sdk/js?client-id=${encodeURIComponent(props.paypal.client_id)}&currency=${encodeURIComponent(props.paypal.currency)}&intent=capture`;
    script.onload = renderPayPalButtons;
    script.onerror = () => {
        paypalError.value = t('checkout.paypal_failed');
    };
    document.head.appendChild(script);
}

function renderPayPalButtons() {
    if (! window.paypal || ! paypalButtons.value) {
        return;
    }

    window.paypal.Buttons({
        style: {
            layout: 'vertical',
            shape: 'rect',
            label: 'pay',
        },
        createOrder: async () => {
            paypalLoading.value = true;
            paypalError.value = '';
            const response = await window.axios.post('/checkout/paypal/create');
            paypalPaymentId = response.data.payment_id;
            return response.data.id;
        },
        onApprove: async (data) => {
            paypalLoading.value = true;
            paypalError.value = '';
            const response = await window.axios.post('/checkout/paypal/capture', {
                payment_id: paypalPaymentId,
                paypal_order_id: data.orderID,
            });
            router.visit(response.data.redirect);
        },
        onError: () => {
            paypalLoading.value = false;
            paypalError.value = t('checkout.paypal_failed');
        },
        onCancel: () => {
            paypalLoading.value = false;
        },
    }).render(paypalButtons.value);
}
</script>

<template>
    <StorefrontLayout>
        <section class="bg-white py-12">
            <div class="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 lg:grid-cols-[1fr_390px] lg:px-8">
                <div>
                    <h1 class="text-4xl font-semibold text-slate-950">{{ t('checkout.title') }}</h1>
                    <p class="mt-3 text-lg text-slate-600">{{ t('checkout.subtitle') }}</p>

                    <div class="mt-8 space-y-4">
                        <article v-for="item in cart.items" :key="item.id" class="grid gap-4 rounded-lg border border-slate-200 bg-white p-4 sm:grid-cols-[110px_1fr_auto]">
                            <StudyCover :product="item.product" compact />
                            <div>
                                <p class="text-xl font-semibold text-slate-950">{{ item.product.title }}</p>
                                <p class="mt-2 text-sm text-slate-500">Qty: {{ item.quantity }}</p>
                            </div>
                            <p class="text-lg font-semibold text-emerald-800">{{ item.total }}</p>
                        </article>
                    </div>
                </div>

                <aside class="lg:sticky lg:top-28 lg:self-start">
                    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-lg">
                        <CartSummary :cart="cart" checkout bare />

                        <div class="mt-6 border-t border-slate-200 pt-6">
                            <p class="mb-3 text-base font-semibold text-slate-950">{{ t('checkout.payment_method') }}</p>
                            <div class="space-y-3">
                                <button
                                    type="button"
                                    class="grid w-full grid-cols-[auto_1fr] gap-3 rounded-lg border p-4 text-start transition"
                                    :class="method === 'paypal' ? 'border-emerald-700 bg-emerald-50/40' : 'border-slate-200 hover:border-slate-300'"
                                    :disabled="!paypal.enabled"
                                    @click="method = 'paypal'"
                                >
                                    <span class="mt-1 grid h-5 w-5 place-items-center rounded-full border" :class="method === 'paypal' ? 'border-emerald-700 bg-emerald-700' : 'border-slate-300'">
                                        <span v-if="method === 'paypal'" class="h-2 w-2 rounded-full bg-white" />
                                    </span>
                                    <span>
                                        <span class="flex items-center gap-2 font-semibold text-slate-950"><CreditCard class="h-5 w-5" /> {{ t('checkout.paypal') }}</span>
                                        <span class="mt-1 block text-sm text-slate-500">{{ paypal.enabled ? t('checkout.paypal_help') : t('checkout.paypal_unavailable') }}</span>
                                    </span>
                                </button>

                                <button
                                    type="button"
                                    class="grid w-full grid-cols-[auto_1fr] gap-3 rounded-lg border p-4 text-start transition"
                                    :class="method === 'bank_transfer' ? 'border-emerald-700 bg-emerald-50/40' : 'border-slate-200 hover:border-slate-300'"
                                    @click="method = 'bank_transfer'"
                                >
                                    <span class="mt-1 grid h-5 w-5 place-items-center rounded-full border" :class="method === 'bank_transfer' ? 'border-emerald-700 bg-emerald-700' : 'border-slate-300'">
                                        <span v-if="method === 'bank_transfer'" class="h-2 w-2 rounded-full bg-white" />
                                    </span>
                                    <span>
                                        <span class="flex items-center gap-2 font-semibold text-slate-950"><Landmark class="h-5 w-5" /> {{ t('checkout.bank_transfer') }}</span>
                                        <span class="mt-1 block text-sm text-slate-500">{{ t('checkout.bank_transfer_help') }}</span>
                                    </span>
                                </button>
                            </div>
                        </div>

                        <div v-if="method === 'paypal'" class="mt-5">
                            <div v-if="paypal.enabled" ref="paypalButtons" class="min-h-[92px]" />
                            <p v-if="paypalLoading" class="mt-3 text-sm text-slate-500">{{ t('checkout.paypal_processing') }}</p>
                            <p v-if="paypalError" class="mt-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ paypalError }}</p>
                        </div>

                        <form v-else class="mt-5 space-y-4" @submit.prevent="submitTransfer">
                            <label class="block">
                                <span class="text-sm font-semibold text-slate-800">{{ t('checkout.reference') }}</span>
                                <textarea v-model="form.reference_number" rows="3" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-700" :placeholder="t('checkout.reference_placeholder')" />
                                <span v-if="form.errors.reference_number" class="mt-1 block text-sm text-red-600">{{ form.errors.reference_number }}</span>
                            </label>

                            <label class="block rounded-lg border border-dashed border-slate-300 p-4 text-center">
                                <UploadCloud class="mx-auto h-7 w-7 text-slate-500" />
                                <span class="mt-2 block text-sm font-semibold text-slate-800">{{ t('checkout.proof') }}</span>
                                <span class="mt-1 block text-xs text-slate-500">{{ form.proof?.name || t('checkout.proof_help') }}</span>
                                <input type="file" class="sr-only" accept=".pdf,.jpg,.jpeg,.png" @change="setProof">
                                <span v-if="form.errors.proof" class="mt-1 block text-sm text-red-600">{{ form.errors.proof }}</span>
                            </label>

                            <button type="submit" class="inline-flex h-12 w-full items-center justify-center rounded-lg bg-emerald-700 px-4 text-sm font-semibold text-white transition hover:bg-emerald-800" :disabled="form.processing">
                                {{ t('checkout.submit_transfer') }}
                            </button>
                        </form>

                        <div class="mt-5 flex gap-3 rounded-lg bg-slate-50 p-4 text-sm leading-6 text-slate-600">
                            <LockKeyhole class="mt-1 h-5 w-5 shrink-0 text-slate-700" />
                            <div>
                                <p class="font-semibold text-slate-950">{{ t('checkout.access_note_title') }}</p>
                                <p>{{ t('checkout.access_note') }}</p>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </section>
    </StorefrontLayout>
</template>
