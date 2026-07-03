<?php

namespace App\Services\Storefront;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

class CartService
{
    public function activeCart(Request $request, bool $create = true): ?Cart
    {
        $user = $request->user('web');
        $query = Cart::query()->where('status', 'active');

        if ($user) {
            $query->where('user_id', $user->id);

            $cart = $query->first();

            if (! $cart && $create) {
                $cart = Cart::create([
                    'user_id' => $user->id,
                    'session_id' => null,
                    'status' => 'active',
                ]);
            }
        } else {
            $sessionId = $request->session()->getId();
            $cartId = $request->session()->get('cart_id');

            if ($cartId) {
                $query->whereKey($cartId)->whereNull('user_id');
            } else {
                $query->where('session_id', $sessionId)->whereNull('user_id');
            }

            $cart = $query->first();

            if (! $cart && $create) {
                $cart = Cart::create([
                    'user_id' => null,
                    'session_id' => $sessionId,
                    'status' => 'active',
                ]);
            }
        }

        if ($cart && ! $user) {
            $request->session()->put('cart_id', $cart->id);
        }

        return $cart?->load(['items.product.cover', 'items.product.documents']);
    }

    public function addProduct(Request $request, Product $product): Cart
    {
        $cart = $this->activeCart($request);

        $item = $cart->items()->firstOrNew(['product_id' => $product->id]);
        $item->fill([
            'quantity' => 1,
            'unit_price_cents' => $product->price_cents,
        ]);
        $item->save();

        return $cart->fresh(['items.product.cover', 'items.product.documents']);
    }

    public function removeItem(Request $request, CartItem $item): void
    {
        $cart = $this->activeCart($request, false);

        abort_unless($cart && $item->cart_id === $cart->id, 404);

        $item->delete();
    }

    public function mergeSessionCartIntoUser(Request $request, User $user): void
    {
        $sessionCart = Cart::query()
            ->with('items.product')
            ->where('status', 'active')
            ->whereNull('user_id')
            ->where(function ($query) use ($request) {
                $query->where('session_id', $request->session()->getId());

                if ($request->session()->has('cart_id')) {
                    $query->orWhere('id', $request->session()->get('cart_id'));
                }
            })
            ->first();

        if (! $sessionCart) {
            return;
        }

        $userCart = Cart::firstOrCreate(
            ['user_id' => $user->id, 'status' => 'active'],
            ['session_id' => null],
        );

        foreach ($sessionCart->items as $item) {
            if (! $item->product || $item->product->status !== 'active') {
                continue;
            }

            $userCart->items()->updateOrCreate(
                ['product_id' => $item->product_id],
                [
                    'quantity' => 1,
                    'unit_price_cents' => $item->product->price_cents,
                ],
            );
        }

        $sessionCart->items()->delete();
        $sessionCart->delete();
        $request->session()->forget('cart_id');
    }

    public function summary(Request $request): ?Cart
    {
        return $this->activeCart($request, false);
    }
}
