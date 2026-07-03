<?php

namespace App\Services\Storefront;

use App\Models\BankTransfer;
use App\Models\Cart;
use App\Models\Media;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function placeBankTransfer(User $user, Cart $cart, array $data, ?UploadedFile $proof): Order
    {
        return DB::transaction(function () use ($user, $cart, $data, $proof) {
            $order = $this->createOrderFromCart($user, $cart, 'bank_transfer');

            $bankTransfer = BankTransfer::create([
                'order_id' => $order->id,
                'user_id' => $user->id,
                'reference_number' => $data['reference_number'] ?? null,
                'status' => 'pending',
                'amount_cents' => $order->total_cents,
                'currency' => $order->currency,
            ]);

            if ($proof) {
                $media = $this->storeTransferProof($proof);
                $bankTransfer->update(['proof_media_id' => $media->id]);
            }

            $this->closeCart($cart);

            return $order->load(['items.product.documents', 'bankTransfer.proofMedia']);
        });
    }

    public function createPendingPaypalPayment(User $user, Cart $cart): Payment
    {
        return DB::transaction(function () use ($user, $cart) {
            $order = $this->createOrderFromCart($user, $cart, 'paypal');
            $paypalCurrency = strtoupper((string) config('services.paypal.checkout_currency', 'USD'));
            $paypalAmountCents = $this->paypalAmountCents($order, $paypalCurrency);

            return Payment::create([
                'order_id' => $order->id,
                'provider' => 'paypal',
                'provider_reference' => null,
                'status' => 'pending',
                'amount_cents' => $paypalAmountCents,
                'currency' => $paypalCurrency,
                'payload' => [
                    'local_currency' => $order->currency,
                    'local_total_cents' => $order->total_cents,
                    'paypal_currency' => $paypalCurrency,
                    'paypal_amount_cents' => $paypalAmountCents,
                    'egp_to_paypal_rate' => (float) config('services.paypal.egp_to_checkout_rate', 0),
                ],
            ]);
        });
    }

    public function attachPaypalReference(Payment $payment, string $paypalOrderId, array $payload): void
    {
        $payment->update([
            'provider_reference' => $paypalOrderId,
            'payload' => [
                ...($payment->payload ?? []),
                'create_response' => $payload,
            ],
        ]);
    }

    public function failPaypalPayment(Payment $payment, array $payload): void
    {
        $payment->update([
            'status' => 'failed',
            'payload' => [
                ...($payment->payload ?? []),
                ...$payload,
            ],
        ]);
    }

    public function capturePaypalPayment(Payment $payment, array $capture): Order
    {
        return DB::transaction(function () use ($payment, $capture) {
            $payment->load('order.items');

            if ($payment->status === 'completed') {
                return $payment->order;
            }

            if (($capture['status'] ?? null) !== 'COMPLETED') {
                Log::error('PayPal capture failed.', [
                    'payment_id' => $payment->id,
                    'paypal_order_id' => $payment->provider_reference,
                    'capture_status' => $capture['status'] ?? null,
                    'capture_response' => $capture,
                ]);

                $this->failPaypalPayment($payment, [
                    'capture_response' => $capture,
                ]);

                throw ValidationException::withMessages([
                    'paypal' => __('storefront.checkout.paypal_failed'),
                ]);
            }
            
            [$currency, $amountCents] = $this->capturedAmount($capture);

            if ($currency !== $payment->currency || $amountCents !== $payment->amount_cents) {
                $this->failPaypalPayment($payment, [
                    'capture_response' => $capture,
                ]);

                throw ValidationException::withMessages([
                    'paypal' => __('storefront.checkout.paypal_amount_mismatch'),
                ]);
            }

            $payment->update([
                'status' => 'completed',
                'payload' => [...($payment->payload ?? []), 'capture_response' => $capture],
            ]);

            $payment->order->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            $this->grantPurchases($payment->order);

            Cart::query()
                ->where('user_id', $payment->order->user_id)
                ->where('status', 'active')
                ->update(['status' => 'converted']);

            return $payment->order->fresh(['items.product.documents']);
        });
    }

    protected function createOrderFromCart(User $user, Cart $cart, string $paymentMethod): Order
    {
        $cart->load(['items.product']);
        $items = $cart->items->filter(fn ($item) => $item->product && $item->product->status === 'active');

        if ($items->isEmpty() || $items->count() !== $cart->items->count()) {
            throw ValidationException::withMessages([
                'cart' => __('storefront.checkout.cart_invalid'),
            ]);
        }

        $subtotalCents = $items->sum(fn ($item) => $item->product->price_cents);
        $currency = $items->first()->product->currency;

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => $this->orderNumber(),
            'status' => 'pending',
            'payment_method' => $paymentMethod,
            'subtotal_cents' => $subtotalCents,
            'total_cents' => $subtotalCents,
            'currency' => $currency,
            'paid_at' => null,
        ]);

        foreach ($items as $item) {
            $order->items()->create([
                'product_id' => $item->product_id,
                'title' => $item->product->title(app()->getLocale()),
                'quantity' => 1,
                'unit_price_cents' => $item->product->price_cents,
                'total_cents' => $item->product->price_cents,
            ]);
        }

        return $order;
    }

    protected function closeCart(Cart $cart): void
    {
        $cart->update(['status' => 'converted']);
    }

    protected function storeTransferProof(UploadedFile $proof): Media
    {
        $path = $proof->store('bank-transfer-proofs');

        return Media::create([
            'collection_name' => 'bank_transfer_proofs',
            'file_type' => str_starts_with((string) $proof->getMimeType(), 'image/') ? 'image' : 'document',
            'disk' => 'local',
            'path' => $path,
            'original_name' => $proof->getClientOriginalName(),
            'mime_type' => $proof->getMimeType(),
            'size' => $proof->getSize(),
        ]);
    }

    protected function grantPurchases(Order $order): void
    {
        foreach ($order->items as $item) {
            if (! $item->product_id) {
                continue;
            }

            Purchase::updateOrCreate(
                [
                    'user_id' => $order->user_id,
                    'product_id' => $item->product_id,
                ],
                [
                    'order_id' => $order->id,
                    'purchased_at' => now(),
                ],
            );
        }
    }

    protected function paypalAmountCents(Order $order, string $paypalCurrency): int
    {
        if ($order->currency === $paypalCurrency) {
            return $order->total_cents;
        }

        if ($order->currency !== 'EGP') {
            throw ValidationException::withMessages([
                'paypal' => __('storefront.checkout.paypal_currency_unsupported'),
            ]);
        }

        $rate = (float) config('services.paypal.egp_to_checkout_rate', 0);

        if ($rate <= 0) {
            throw ValidationException::withMessages([
                'paypal' => __('storefront.checkout.paypal_rate_missing'),
            ]);
        }

        return (int) round(($order->total_cents / 100) * $rate * 100);
    }

    protected function capturedAmount(array $capture): array
    {
        $amount = data_get($capture, 'purchase_units.0.payments.captures.0.amount', []);
        $currency = $amount['currency_code'] ?? '';
        $value = (float) ($amount['value'] ?? 0);

        return [$currency, (int) round($value * 100)];
    }

    protected function orderNumber(): string
    {
        do {
            $number = 'ORD-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }
}
