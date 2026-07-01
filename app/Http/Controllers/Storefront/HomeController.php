<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\Storefront\FaqResource;
use App\Http\Resources\Storefront\ProductResource;
use App\Models\Faq;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        if (! Schema::hasTable('products') || ! Schema::hasTable('faqs')) {
            return Inertia::render('Storefront/Home', [
                'products' => [],
                'faqs' => [],
            ]);
        }

        $products = Product::query()
            ->with(['cover', 'documents'])
            ->withCount('documents')
            ->where('status', 'active')
            ->latest('published_at')
            ->limit(2)
            ->get();

        $faqs = Faq::query()
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->limit(4)
            ->get();

        return Inertia::render('Storefront/Home', [
            'products' => ProductResource::collection($products)->resolve($request),
            'faqs' => FaqResource::collection($faqs)->resolve($request),
        ]);
    }
}
