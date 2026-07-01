<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\Storefront\LibraryOrderResource;
use App\Http\Resources\Storefront\PurchaseResource;
use App\Models\Media;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LibraryController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $purchases = $user->purchases()
            ->with(['product.cover', 'product.documents', 'order'])
            ->latest('purchased_at')
            ->get();

        $pendingOrders = Order::query()
            ->with(['items.product.cover', 'items.product.documents', 'bankTransfer.proofMedia'])
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'cancelled'])
            ->latest()
            ->get();

        return Inertia::render('Storefront/Library', [
            'purchases' => PurchaseResource::collection($purchases)->resolve($request),
            'pendingOrders' => LibraryOrderResource::collection($pendingOrders)->resolve($request),
        ]);
    }

    public function download(Request $request, Media $media): StreamedResponse
    {
        abort_unless($media->file_type === 'document', 404);
        abort_unless($media->mediable_type === \App\Models\Product::class, 404);

        $hasPurchase = $request->user()
            ->purchases()
            ->where('product_id', $media->mediable_id)
            ->exists();

        abort_unless($hasPurchase, 403);

        return Storage::disk($media->disk)->download($media->path, $media->original_name ?: basename($media->path));
    }
}
