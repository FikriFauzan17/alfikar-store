<?php

namespace App\Http\Controllers;

use App\Http\Requests\CatalogRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSlugRedirect;
use App\Services\ProductMetricRecorder;
use App\Services\PublicSiteSettings;
use App\Services\WhatsAppOrderBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(CatalogRequest $request, PublicSiteSettings $settings): View|RedirectResponse
    {
        $filters = $request->validated();
        $search = trim((string) ($filters['q'] ?? ''));
        $categorySlug = $filters['category'] ?? null;
        $sort = $filters['sort'] ?? 'newest';
        $sorting = [
            'newest' => ['published_at', 'desc'],
            'title' => ['title', 'asc'],
            'price_asc' => ['unit_price', 'asc'],
            'price_desc' => ['unit_price', 'desc'],
        ];

        if (! array_key_exists($sort, $sorting)) {
            return redirect()->route('products.index');
        }

        $query = Product::query()
            ->published()
            ->select([
                'id',
                'category_id',
                'code',
                'slug',
                'title',
                'author',
                'stock',
                'unit_price',
                'wholesale_price',
                'wholesale_minimum',
                'published_at',
            ])
            ->with([
                'category:id,name,slug',
                'images' => fn (HasMany $query) => $query->select([
                    'id',
                    'product_id',
                    'disk',
                    'path',
                    'alt_text',
                    'variants',
                    'width',
                    'height',
                    'file_size',
                    'mime_type',
                    'sort_order',
                ])->orderBy('sort_order'),
            ]);

        if ($search !== '') {
            $isFullTextSupported = in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);
            $searchTokens = preg_split('/[^\p{L}\p{N}_]+/u', $search, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $fullTextSearch = implode(' ', array_map(
                static fn (string $token): string => $token.'*',
                $searchTokens,
            ));

            $query->where(function (Builder $query) use ($search, $fullTextSearch, $isFullTextSupported): void {
                if ($isFullTextSupported && $fullTextSearch !== '') {
                    $query->whereFullText(['title', 'author', 'publisher'], $fullTextSearch, ['mode' => 'boolean'])
                        ->orWhere('code', 'like', '%'.$search.'%')
                        ->orWhere('isbn', 'like', '%'.$search.'%');

                    return;
                }

                $query->where('title', 'like', '%'.$search.'%')
                    ->orWhere('author', 'like', '%'.$search.'%')
                    ->orWhere('publisher', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%')
                    ->orWhere('isbn', 'like', '%'.$search.'%');
            });
        }

        if (is_string($categorySlug) && $categorySlug !== '') {
            $query->whereHas('category', fn (Builder $query) => $query->where('slug', $categorySlug));
        }

        [$sortColumn, $sortDirection] = $sorting[$sort];
        $products = $query->orderBy($sortColumn, $sortDirection)->orderByDesc('id')->paginate(12)->appends($filters);

        abort_if($products->currentPage() > $products->lastPage(), 404);

        return view('catalog.index', [
            'products' => $products,
            'categories' => Category::query()
                ->where('is_published', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'slug']),
            'filters' => $filters,
            'siteName' => $settings->values(['profile_name'])['profile_name'] ?? 'Toko Buku Alfikar',
            'breadcrumbStructuredData' => [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda', 'item' => route('home')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Produk', 'item' => route('products.index')],
                ],
            ],
        ]);
    }

    public function show(
        string $product,
        WhatsAppOrderBuilder $whatsapp,
        ProductMetricRecorder $metrics,
    ): View|RedirectResponse {
        $requestedSlug = $product;
        $product = Product::query()
            ->published()
            ->with([
                'category:id,name,slug',
                'images' => fn (HasMany $query) => $query->orderBy('sort_order'),
            ])
            ->where('slug', $requestedSlug)
            ->first();

        if ($product === null) {
            $slugRedirect = ProductSlugRedirect::query()->where('slug', $requestedSlug)->first();
            $redirectTarget = $slugRedirect === null
                ? null
                : Product::query()->published()->whereKey($slugRedirect->product_id)->first(['id', 'slug']);

            if ($redirectTarget === null) {
                abort(404);
            }

            return redirect()->route('products.show', ['product' => $redirectTarget->slug], 301);
        }

        $metrics->recordOnce($product, 'product_view', request()->session()->getId());

        return $this->productView($product, $whatsapp);
    }

    public function preview(Product $product, WhatsAppOrderBuilder $whatsapp): Response
    {
        $product->load([
            'category:id,name,slug',
            'images' => fn (HasMany $query) => $query->orderBy('sort_order'),
        ]);
        $view = $this->productView($product, $whatsapp, true);

        return response($view->render(), 200, [
            'X-Robots-Tag' => 'noindex, nofollow',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    private function productView(
        Product $product,
        WhatsAppOrderBuilder $whatsapp,
        bool $isPreview = false,
    ): View {
        $relatedProducts = Product::query()
            ->published()
            ->select(['id', 'category_id', 'code', 'slug', 'title', 'author', 'stock', 'unit_price', 'wholesale_price', 'wholesale_minimum', 'published_at'])
            ->with([
                'category:id,name,slug',
                'images' => fn (HasMany $query) => $query->select([
                    'id',
                    'product_id',
                    'disk',
                    'path',
                    'alt_text',
                    'variants',
                    'width',
                    'height',
                    'file_size',
                    'mime_type',
                    'sort_order',
                ])->orderBy('sort_order'),
            ])
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->getKey())
            ->orderByDesc('published_at')
            ->limit(4)
            ->get();

        $productUrl = $isPreview
            ? request()->url()
            : route('products.show', ['product' => $product->slug]);
        $bookStructuredData = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Book',
            'name' => $product->title,
            'author' => ['@type' => 'Person', 'name' => $product->author],
            'publisher' => ['@type' => 'Organization', 'name' => $product->publisher],
            'isbn' => $product->isbn ?: null,
            'sku' => $product->code,
            'image' => $product->images->map(function ($image): string {
                /** @var FilesystemAdapter $disk */
                $disk = Storage::disk($image->disk);

                return $disk->url($image->path);
            })->all(),
            'description' => $product->meta_description ?: Str::limit(strip_tags($product->description), 500),
            'url' => $productUrl,
            'offers' => [
                '@type' => 'Offer',
                'priceCurrency' => 'IDR',
                'price' => $product->unit_price,
                'availability' => (int) $product->stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'url' => $productUrl,
            ],
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
        $breadcrumbStructuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda', 'item' => route('home')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => $product->category->name, 'item' => route('products.index', ['category' => $product->category->slug])],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $product->title, 'item' => $productUrl],
            ],
        ];

        return view('catalog.show', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
            'whatsappAvailable' => $whatsapp->isAvailable(),
            'isPreview' => $isPreview,
            'bookStructuredData' => $bookStructuredData,
            'breadcrumbStructuredData' => $breadcrumbStructuredData,
        ]);
    }
}
