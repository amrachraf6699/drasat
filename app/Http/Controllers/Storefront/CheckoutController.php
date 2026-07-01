<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\Storefront\CartResource;
use App\Models\Payment;
use App\Services\Storefront\CartService;
use App\Services\Storefront\CheckoutService;
use App\Services\Storefront\PayPalClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function __construct(
        private CartService $carts,
        private CheckoutService $checkout,
        private PayPalClient $paypal,
    ) {
    }

    public function show(Request $request): Response|RedirectResponse
    {
        $cart = $this->carts->activeCart($request, false);

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('studies.index')->with('status', __('storefront.checkout.empty_cart'));
        }

        return Inertia::render('Storefront/Checkout', [
            'cart' => CartResource::make($cart)->resolve($request),
            'paypal' => [
                'enabled' => $this->paypal->isConfigured() && (float) config('services.paypal.egp_to_checkout_rate', 0) > 0,
                'client_id' => config('services.paypal.client_id'),
                'currency' => config('services.paypal.checkout_currency', 'USD'),
            ],
        ]);
    }

    public function bankTransfer(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'reference_number' => ['nullable', 'string', 'max:120'],
            'proof' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $cart = $this->carts->activeCart($request, false);

        if (! $cart || $cart->items->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => __('storefront.checkout.empty_cart'),
            ]);
        }

        $this->checkout->placeBankTransfer($request->user(), $cart, $data, $request->file('proof'));

        return redirect()->route('library.index')->with('status', __('storefront.checkout.transfer_submitted'));
    }

    public function createPaypalOrder(Request $request): JsonResponse
    {
        $cart = $this->carts->activeCart($request, false);

        if (! $cart || $cart->items->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => __('storefront.checkout.empty_cart'),
            ]);
        }

        $payment = $this->checkout->createPendingPaypalPayment($request->user(), $cart);
        $paypalOrder = $this->paypal->createOrder($payment->load('order'));

        $paypalOrderId = $paypalOrder['id'] ?? null;

        if (! $paypalOrderId) {
            throw ValidationException::withMessages([
                'paypal' => __('storefront.checkout.paypal_failed'),
            ]);
        }

        $this->checkout->attachPaypalReference($payment, $paypalOrderId, $paypalOrder);

        return response()->json([
            'id' => $paypalOrderId,
            'payment_id' => $payment->id,
        ]);
    }

    public function capturePaypalOrder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'payment_id' => ['required', 'integer', 'exists:payments,id'],
            'paypal_order_id' => ['required', 'string'],
        ]);

        $payment = Payment::query()->with('order')->findOrFail($data['payment_id']);

        abort_unless($payment->order?->user_id === $request->user()->id, 403);
        abort_unless($payment->provider === 'paypal' && $payment->provider_reference === $data['paypal_order_id'], 404);

        $capture = $this->paypal->capture($data['paypal_order_id']);
        $order = $this->checkout->capturePaypalPayment($payment, $capture);

        return response()->json([
            'status' => 'completed',
            'redirect' => route('library.index'),
            'order_number' => $order->order_number,
        ]);
    }
}
