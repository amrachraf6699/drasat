<?php

namespace Tests\Feature;

use App\Models\BankTransfer;
use App\Models\Cart;
use App\Models\Media;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class StorefrontCommerceTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_storefront_pages_render_resource_shapes(): void
    {
        $this->seed(DatabaseSeeder::class);
        $product = Product::query()->where('status', 'active')->firstOrFail();

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Storefront/Home')
                ->has('products.0.id')
                ->has('products.0.slug')
                ->has('products.0.title')
                ->has('products.0.price')
                ->has('products.0.href')
                ->has('faqs.0.question')
                ->has('cartSummary.items')
                ->where('auth.user', null)
            );

        $this->get('/studies')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Storefront/Studies')
                ->has('products.data.0.title')
                ->has('products.meta.total')
            );

        $this->get("/studies/{$product->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Storefront/StudyDetail')
                ->where('product.id', $product->id)
                ->has('product.documents')
                ->has('faqs')
            );
    }

    public function test_user_registration_merges_guest_cart_and_shares_auth_user(): void
    {
        $this->seed(DatabaseSeeder::class);
        $product = Product::query()->where('status', 'active')->firstOrFail();

        $this->post('/cart/items', ['product_id' => $product->id]);

        $this->post('/register', [
            'name' => 'Frontend User',
            'email' => 'frontend@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect('/library');

        $user = User::where('email', 'frontend@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('cart_items', ['product_id' => $product->id]);
        $this->assertTrue(Cart::where('user_id', $user->id)->where('status', 'active')->exists());

        $this->get('/library')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Storefront/Library')
                ->where('auth.user.email', 'frontend@example.com')
                ->where('cartSummary.count', 1)
            );
    }

    public function test_bank_transfer_checkout_creates_pending_order_and_transfer(): void
    {
        Storage::fake('local');
        $this->seed(DatabaseSeeder::class);
        $user = User::where('email', 'user@drasa.test')->firstOrFail();
        $product = Product::query()->where('status', 'active')->firstOrFail();

        $this->actingAs($user)->post('/cart/items', ['product_id' => $product->id]);

        $this->actingAs($user)
            ->post('/checkout/bank-transfer', [
                'reference_number' => 'TRX-100',
                'proof' => UploadedFile::fake()->image('transfer.jpg'),
            ])
            ->assertRedirect('/library');

        $order = Order::where('user_id', $user->id)->latest()->firstOrFail();
        $this->assertSame('pending', $order->status);
        $this->assertSame('bank_transfer', $order->payment_method);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        $transfer = BankTransfer::where('order_id', $order->id)->firstOrFail();
        $this->assertSame('pending', $transfer->status);
        $this->assertSame('TRX-100', $transfer->reference_number);
        $this->assertNotNull($transfer->proof_media_id);
        $this->assertSame('converted', Cart::where('user_id', $user->id)->latest()->firstOrFail()->status);
    }

    public function test_paypal_checkout_uses_converted_currency_and_grants_purchase_on_capture(): void
    {
        config([
            'services.paypal.client_id' => 'client',
            'services.paypal.secret' => 'secret',
            'services.paypal.checkout_currency' => 'USD',
            'services.paypal.egp_to_checkout_rate' => 0.02,
        ]);

        Http::fake([
            'api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'token']),
            'api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response(['id' => 'PAYPAL-ORDER-1', 'status' => 'CREATED']),
            'api-m.sandbox.paypal.com/v2/checkout/orders/PAYPAL-ORDER-1/capture' => Http::response([
                'status' => 'COMPLETED',
                'purchase_units' => [[
                    'payments' => [
                        'captures' => [[
                            'amount' => [
                                'currency_code' => 'USD',
                                'value' => '25.00',
                            ],
                        ]],
                    ],
                ]],
            ]),
        ]);

        $this->seed(DatabaseSeeder::class);
        $user = User::where('email', 'user@drasa.test')->firstOrFail();
        $product = Product::query()->where('status', 'active')->where('price_cents', 125000)->firstOrFail();

        $this->actingAs($user)->post('/cart/items', ['product_id' => $product->id]);

        $createResponse = $this->actingAs($user)
            ->postJson('/checkout/paypal/create')
            ->assertOk()
            ->assertJsonPath('id', 'PAYPAL-ORDER-1')
            ->json();

        $payment = Payment::findOrFail($createResponse['payment_id']);
        $this->assertSame('USD', $payment->currency);
        $this->assertSame(2500, $payment->amount_cents);
        $this->assertSame('PAYPAL-ORDER-1', $payment->provider_reference);

        $this->actingAs($user)
            ->postJson('/checkout/paypal/capture', [
                'payment_id' => $payment->id,
                'paypal_order_id' => 'PAYPAL-ORDER-1',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'completed');

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'completed']);
        $this->assertDatabaseHas('orders', ['id' => $payment->order_id, 'status' => 'paid']);
        $this->assertDatabaseHas('purchases', ['user_id' => $user->id, 'product_id' => $product->id]);
    }

    public function test_downloads_are_protected_by_purchase_ownership(): void
    {
        Storage::fake('local');
        $this->seed(DatabaseSeeder::class);
        $product = Product::query()->where('status', 'active')->firstOrFail();
        $owner = User::where('email', 'user@drasa.test')->firstOrFail();
        $otherUser = User::factory()->create();

        Storage::disk('local')->put('product-documents/report.pdf', 'report-content');
        $document = $product->documents()->create([
            'collection_name' => 'documents',
            'file_type' => 'document',
            'disk' => 'local',
            'path' => 'product-documents/report.pdf',
            'original_name' => 'Full Report.pdf',
            'mime_type' => 'application/pdf',
            'size' => 14,
        ]);

        $this->actingAs($otherUser)
            ->get(route('library.documents.download', $document))
            ->assertForbidden();

        Purchase::create([
            'user_id' => $owner->id,
            'product_id' => $product->id,
            'order_id' => null,
            'purchased_at' => now(),
        ]);

        $this->actingAs($owner)
            ->get(route('library.documents.download', $document))
            ->assertOk();
    }

    public function test_oauth_callback_upserts_user_provider_columns(): void
    {
        $socialUser = Mockery::mock(SocialiteUser::class);
        $socialUser->shouldReceive('getId')->andReturn('google-123');
        $socialUser->shouldReceive('getName')->andReturn('Google User');
        $socialUser->shouldReceive('getNickname')->andReturn(null);
        $socialUser->shouldReceive('getEmail')->andReturn('google@example.com');
        $socialUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.png');

        $provider = Mockery::mock();
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($socialUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->get('/auth/google/callback')
            ->assertRedirect('/library');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'google@example.com',
            'oauth_provider' => 'google',
            'oauth_provider_id' => 'google-123',
            'oauth_avatar' => 'https://example.com/avatar.png',
        ]);
    }
}
