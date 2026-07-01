<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['q', 'status', 'documents', 'sort']);
        $query = Product::query()->with(['cover', 'documents']);

        $query
            ->when($filters['q'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('sku', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere(function ($query) use ($search) {
                            foreach ($this->translatedSearchColumns(['title', 'short_description']) as $column) {
                                $query->orWhere($column, 'like', "%{$search}%");
                            }
                        });
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when(($filters['documents'] ?? null) === 'with', fn ($query) => $query->has('documents'))
            ->when(($filters['documents'] ?? null) === 'without', fn ($query) => $query->doesntHave('documents'));

        match ($filters['sort'] ?? 'newest') {
            'oldest' => $query->oldest(),
            'price_high' => $query->orderByDesc('price_cents'),
            'price_low' => $query->orderBy('price_cents'),
            default => $query->latest(),
        };

        return Inertia::render('Admin/Products', [
            'filters' => $filters,
            'products' => $query
                ->paginate(10)
                ->withQueryString()
                ->through(fn (Product $product) => $this->serializeProduct($product)),
        ]);
    }

    public function show(Product $product): Response
    {
        $product->load([
            'cover',
            'documents',
            'purchases.user',
            'purchases.order',
        ]);

        $data = $this->serializeProduct($product);

        return Inertia::render('Admin/Detail', [
            'title' => $data['title_en'] ?: $data['title_ar'] ?: __('admin.products.title'),
            'subtitle' => $data['sku'] ?: __('admin.common.no_sku'),
            'backHref' => route('admin.products.index'),
            'stats' => [
                ['label' => __('admin.common.price'), 'value' => number_format($product->price_cents / 100, 2).' '.$product->currency],
                ['label' => __('admin.common.status'), 'value' => __('admin.common.statuses.'.$product->status)],
                ['label' => __('admin.common.documents'), 'value' => $product->documents->count()],
                ['label' => __('admin.users.purchases'), 'value' => $product->purchases->count()],
            ],
            'fields' => [
                ['label' => __('admin.common.key'), 'value' => $product->slug],
                ['label' => __('admin.common.title_en'), 'value' => $data['title_en']],
                ['label' => __('admin.common.title_ar'), 'value' => $data['title_ar']],
                ['label' => __('admin.common.short_description_en'), 'value' => $data['short_description_en']],
                ['label' => __('admin.common.short_description_ar'), 'value' => $data['short_description_ar']],
                ['label' => __('admin.common.description_en'), 'value' => $data['description_en']],
                ['label' => __('admin.common.description_ar'), 'value' => $data['description_ar']],
                ['label' => __('admin.common.created'), 'value' => $product->created_at?->format('Y-m-d H:i')],
            ],
            'sections' => [
                [
                    'title' => __('admin.users.purchases'),
                    'columns' => [
                        ['key' => 'customer', 'label' => __('admin.common.customer')],
                        ['key' => 'order_number', 'label' => __('admin.common.order')],
                        ['key' => 'purchased_at', 'label' => __('admin.common.date')],
                    ],
                    'rows' => $product->purchases->map(fn ($purchase) => [
                        'id' => $purchase->id,
                        'customer' => $purchase->user?->name ?? __('admin.common.guest'),
                        'order_number' => $purchase->order?->order_number,
                        'purchased_at' => $purchase->purchased_at?->format('Y-m-d H:i'),
                        'href' => $purchase->order ? route('admin.orders.show', $purchase->order) : null,
                    ])->values(),
                    'showLinks' => true,
                ],
                [
                    'title' => __('admin.common.documents'),
                    'columns' => [
                        ['key' => 'name', 'label' => __('admin.common.name')],
                        ['key' => 'type', 'label' => __('admin.common.input_type')],
                        ['key' => 'size', 'label' => __('admin.common.total')],
                    ],
                    'rows' => $product->documents->map(fn (Media $media) => [
                        'id' => $media->id,
                        'name' => $media->original_name,
                        'type' => $media->mime_type,
                        'size' => number_format(($media->size ?? 0) / 1024, 1).' KB',
                    ])->values(),
                ],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($request, $data) {
            $product = Product::create([
                'sku' => $data['sku'] ?? null,
                'slug' => Str::slug($data['title_en']).'-'.Str::lower(Str::random(5)),
                'price' => $data['price'],
                'currency' => strtoupper($data['currency']),
                'status' => $data['status'],
                'published_at' => $data['status'] === 'active' ? now() : null,
                'title' => $this->translationsFromData($data, 'title'),
                'short_description' => $this->translationsFromData($data, 'short_description'),
                'description' => $this->translationsFromData($data, 'description'),
            ]);

            $this->storeUploads($request, $product);
        });

        return back()->with('status', __('admin.flash.product_created'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validated($request, $product);

        DB::transaction(function () use ($request, $product, $data) {
            $product->update([
                'sku' => $data['sku'] ?? null,
                'price' => $data['price'],
                'currency' => strtoupper($data['currency']),
                'status' => $data['status'],
                'published_at' => $data['status'] === 'active' ? ($product->published_at ?? now()) : null,
                'title' => $this->translationsFromData($data, 'title'),
                'short_description' => $this->translationsFromData($data, 'short_description'),
                'description' => $this->translationsFromData($data, 'description'),
            ]);

            $this->storeUploads($request, $product);
        });

        return back()->with('status', __('admin.flash.product_updated'));
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->load(['cover', 'documents']);

        foreach ($product->documents as $document) {
            Storage::disk($document->disk)->delete($document->path);
        }

        if ($product->cover) {
            Storage::disk($product->cover->disk)->delete($product->cover->path);
        }

        $product->delete();

        return back()->with('status', __('admin.flash.product_deleted'));
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'sku' => ['nullable', 'string', 'max:80'],
            'title_en' => ['required', 'string', 'max:255'],
            'title_ar' => ['required', 'string', 'max:255'],
            'short_description_en' => ['nullable', 'string', 'max:1000'],
            'short_description_ar' => ['nullable', 'string', 'max:1000'],
            'description_en' => ['nullable', 'string'],
            'description_ar' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'status' => ['required', 'in:active,inactive,draft'],
            'cover' => ['nullable', 'image', 'max:4096'],
            'documents' => ['nullable', 'array'],
            'documents.*' => ['file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip', 'max:20480'],
        ]);
    }

    private function storeUploads(Request $request, Product $product): void
    {
        if ($request->hasFile('cover')) {
            if ($product->cover) {
                Storage::disk($product->cover->disk)->delete($product->cover->path);
                $product->cover->delete();
            }

            $file = $request->file('cover');
            $path = $file->store('product-covers', 'public');

            $product->cover()->create([
                'collection_name' => 'cover',
                'file_type' => 'image',
                'disk' => 'public',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        foreach ($request->file('documents', []) as $index => $file) {
            $path = $file->store('product-documents');

            $product->documents()->create([
                'collection_name' => 'documents',
                'file_type' => 'document',
                'disk' => 'local',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'sort_order' => $product->documents()->count() + $index,
            ]);
        }
    }

    private function serializeProduct(Product $product): array
    {
        return [
            'id' => $product->id,
            'sku' => $product->sku,
            'title_en' => $this->translation($product, 'title', 'en'),
            'title_ar' => $this->translation($product, 'title', 'ar'),
            'short_description_en' => $this->translation($product, 'short_description', 'en'),
            'short_description_ar' => $this->translation($product, 'short_description', 'ar'),
            'description_en' => $this->translation($product, 'description', 'en'),
            'description_ar' => $this->translation($product, 'description', 'ar'),
            'price' => $product->price,
            'price_cents' => $product->price_cents,
            'currency' => $product->currency,
            'status' => $product->status,
            'documents_count' => $product->documents->count(),
            'cover_url' => $product->cover?->url,
            'updated_at' => $product->updated_at?->format('Y-m-d H:i'),
        ];
    }

    private function translationsFromData(array $data, string $attribute): array
    {
        $translations = [];

        foreach ($this->locales() as $locale) {
            $translations[$locale] = $data["{$attribute}_{$locale}"] ?? null;
        }

        return $translations;
    }

    private function translation(Product $product, string $attribute, string $locale): mixed
    {
        return $product->getTranslation($attribute, $locale, false);
    }

    private function translatedSearchColumns(array $attributes): array
    {
        $columns = [];

        foreach ($attributes as $attribute) {
            foreach ($this->locales() as $locale) {
                $columns[] = "{$attribute}->{$locale}";
            }
        }

        return $columns;
    }

    private function locales(): array
    {
        return config('app.supported_locales', ['en', 'ar']);
    }
}
