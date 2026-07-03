<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\Storefront\FaqResource;
use App\Http\Resources\Storefront\ProductDetailResource;
use App\Http\Resources\Storefront\ProductResource;
use App\Models\Faq;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['q', 'sort']);
        $user = $request->user('web');
        $query = Product::query()
            ->with(['cover', 'documents'])
            ->withCount('documents')
            ->where('status', 'active');

        if ($user) {
            $query->withExists([
                'purchases as purchased_by_user' => fn ($query) => $query->where('user_id', $user->id),
            ]);
        }

        $query->when($filters['q'] ?? null, function ($query, string $search) {
            $query->where(function ($query) use ($search) {
                $query
                    ->where('sku', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('title->en', 'like', "%{$search}%")
                    ->orWhere('title->ar', 'like', "%{$search}%")
                    ->orWhere('short_description->en', 'like', "%{$search}%")
                    ->orWhere('short_description->ar', 'like', "%{$search}%");
            });
        });

        match ($filters['sort'] ?? 'newest') {
            'price_high' => $query->orderByDesc('price_cents'),
            'price_low' => $query->orderBy('price_cents'),
            default => $query->latest('published_at')->latest(),
        };

        return Inertia::render('Storefront/Studies', [
            'filters' => $filters,
            'products' => ProductResource::collection($query->paginate(12)->withQueryString()),
        ]);
    }

    public function show(Request $request, Product $product): Response
    {
        abort_unless($product->status === 'active', 404);

        $product->load(['cover', 'documents']);

        $relatedProducts = Product::query()
            ->with(['cover', 'documents'])
            ->withCount('documents')
            ->where('status', 'active')
            ->whereKeyNot($product->id)
            ->latest('published_at')
            ->limit(3)
            ->get();

        $faqs = Faq::query()
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Storefront/StudyDetail', [
            'product' => ProductDetailResource::make($product)->resolve($request),
            'relatedProducts' => ProductResource::collection($relatedProducts)->resolve($request),
            'faqs' => FaqResource::collection($faqs)->resolve($request),
        ]);
    }
}
