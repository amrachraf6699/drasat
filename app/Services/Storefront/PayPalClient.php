<?php

namespace App\Services\Storefront;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PayPalClient
{
    public function createOrder(Payment $payment): array
    {
        $this->ensureConfigured();

        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->post($this->baseUrl().'/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => $payment->order?->order_number,
                    'custom_id' => (string) $payment->id,
                    'amount' => [
                        'currency_code' => $payment->currency,
                        'value' => $this->formatAmount($payment->amount_cents),
                    ],
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
            ->post($this->baseUrl()."/v2/checkout/orders/{$providerOrderId}/capture", new \stdClass())
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
