<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\Storefront\FaqResource;
use App\Models\Faq;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class FaqController extends Controller
{
    public function __invoke(Request $request): Response
    {
        if (! Schema::hasTable('faqs')) {
            return Inertia::render('Storefront/Faq', [
                'faqs' => [],
            ]);
        }

        $faqs = Faq::query()
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Storefront/Faq', [
            'faqs' => FaqResource::collection($faqs)->resolve($request),
        ]);
    }
}
