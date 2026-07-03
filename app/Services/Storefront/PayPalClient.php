<?php

namespace App\Services\Storefront;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use stdClass;

class PayPalClient
{
    public function createOrder(Payment $payment): array
    {
        $this->ensureConfigured();
        $order = $payment->order?->loadMissing('items');
        $description = $order?->items?->pluck('title')->filter()->join(', ') ?: config('app.name', 'Drasa');

        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->post($this->baseUrl().'/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'application_context' => [
                    'brand_name' => Str::limit((string) config('app.name', 'Drasa'), 127, ''),
                    'shipping_preference' => 'NO_SHIPPING',
                    'user_action' => 'PAY_NOW',
                ],
                'purchase_units' => [[
                    'reference_id' => $order?->order_number,
                    'invoice_id' => $order?->order_number,
                    'custom_id' => (string) $payment->id,
                    'description' => Str::limit($description, 127, ''),
                    'amount' => [
                        'currency_code' => $payment->currency,
                        'value' => $this->formatAmount($payment->amount_cents),
                        'breakdown' => [
                            'item_total' => [
                                'currency_code' => $payment->currency,
                                'value' => $this->formatAmount($payment->amount_cents),
                            ],
                        ],
                    ],
                    'items' => [[
                        'name' => Str::limit(($order?->order_number ? "Digital studies {$order->order_number}" : 'Digital studies'), 127, ''),
                        'quantity' => '1',
                        'category' => 'DIGITAL_GOODS',
                        'unit_amount' => [
                            'currency_code' => $payment->currency,
                            'value' => $this->formatAmount($payment->amount_cents),
                        ],
                    ]],
                ]],
            ])
            ->throw()
            ->json();

        return $response;
    }

    public function capture(string $providerOrderId): array
    {
        $this->ensureConfigured();

        return Http::withToken($this->accessToken())
            ->acceptJson()
            ->post($this->baseUrl()."/v2/checkout/orders/{$providerOrderId}/capture", new stdClass())
            ->throw()
            ->json();
    }

    public function isConfigured(): bool
    {
        return filled(config('services.paypal.client_id')) && filled(config('services.paypal.secret'));
    }

    protected function accessToken(): string
    {
        $response = Http::asForm()
            ->withBasicAuth(config('services.paypal.client_id'), config('services.paypal.secret'))
            ->post($this->baseUrl().'/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ])
            ->throw()
            ->json();

        return $response['access_token'] ?? throw new RuntimeException('PayPal access token was not returned.');
    }

    protected function ensureConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('PayPal is not configured.');
        }
    }

    protected function baseUrl(): string
    {
        return config('services.paypal.mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    protected function formatAmount(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
