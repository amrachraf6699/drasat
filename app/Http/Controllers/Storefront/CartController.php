<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\Storefront\CartResource;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\Storefront\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function __construct(private CartService $carts)
    {
    }

    public function show(Request $request): Response
    {
        return Inertia::render('Storefront/Cart', [
            'cart' => CartResource::make($this->carts->activeCart($request, false))->resolve($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
        ]);

        $product = Product::query()
            ->where('status', 'active')
            ->findOrFail($data['product_id']);

        $this->carts->addProduct($request, $product);

        return back()->with('status', __('storefront.cart.added'));
    }

    public function destroy(Request $request, CartItem $cartItem): RedirectResponse
    {
        $this->carts->removeItem($request, $cartItem);

        return back()->with('status', __('storefront.cart.removed'));
    }
}
