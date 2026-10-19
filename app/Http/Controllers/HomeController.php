<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Services\PublicSiteSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Response;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(PublicSiteSettings $settings): View
    {
        return $this->homepage($settings);
    }

    public function preview(PublicSiteSettings $settings): Response
    {
        $draftKeys = [
            'home_draft_hero_title',
            'home_draft_hero_description',
            'home_draft_hero_cta_label',
            'home_draft_hero_cta_url',
            'home_draft_sections',
        ];
        $draft = SiteSetting::query()
            ->whereIn('setting_key', $draftKeys)
            ->where('is_public', false)
            ->pluck('setting_value', 'setting_key');

        abort_unless($draft->has('home_draft_sections'), 404);

        $homepageOverrides = $draft
            ->mapWithKeys(fn (mixed $value, string $key): array => [
                'home_'.substr($key, strlen('home_draft_')) => (string) $value,
            ])
            ->all();
        $view = $this->homepage($settings, $homepageOverrides, true);

        return response($view->render(), 200, [
            'X-Robots-Tag' => 'noindex, nofollow',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    private function homepage(
        PublicSiteSettings $settings,
        array $homepageOverrides = [],
        bool $isPreview = false,
    ): View {
        $productColumns = [
            'id',
            'category_id',
            'code',
            'slug',
            'title',
            'author',
            'publisher',
            'description',
            'specifications',
            'stock',
            'unit_price',
            'wholesale_price',
            'wholesale_minimum',
            'is_published',
            'is_featured',
            'is_bestseller',
            'published_at',
            'meta_title',
            'meta_description',
        ];
        $cardColumns = [
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
        ];
        $imageLoader = fn (HasMany $query) => $query->select([
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
        ])->orderBy('sort_order');

        $featuredProduct = Product::query()
            ->published()
            ->select($productColumns)
            ->with(['category:id,name,slug', 'images' => $imageLoader])
            ->where('is_featured', true)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->first();

        $categories = Category::query()
            ->select(['id', 'name', 'slug', 'description', 'image_path', 'sort_order'])
            ->where('is_published', true)
            ->with(['products' => fn (HasMany $query) => $query->published()
                ->whereHas('images')
                ->select(['id', 'category_id'])
                ->with(['images' => $imageLoader])
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->limit(1)])
            ->withCount(['products as published_products_count' => fn (Builder $query) => $query->published()])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(8)
            ->get();

        $services = $settings->values([
            'service_delivery',
            'service_payment',
            'service_returns',
            'service_hours',
        ]);
        $organizationSettings = $settings->values([
            'profile_name',
            'profile_description',
            'contact_email',
            'contact_phone',
            'home_hero_title',
            'home_hero_description',
            'home_hero_cta_label',
            'home_hero_cta_url',
            'home_sections',
        ]);
        $organizationSettings = array_replace($organizationSettings, $homepageOverrides);
        $siteName = filled($organizationSettings['profile_name'] ?? null)
            ? $organizationSettings['profile_name']
            : 'Toko Buku Alfikar';
        $defaultSections = [
            'hero' => ['enabled' => true, 'order' => 1],
            'categories' => ['enabled' => true, 'order' => 2],
            'services' => ['enabled' => true, 'order' => 3],
            'bestsellers' => ['enabled' => true, 'order' => 4],
            'products' => ['enabled' => true, 'order' => 5],
            'articles' => ['enabled' => true, 'order' => 6],
        ];
        $storedSections = json_decode($organizationSettings['home_sections'] ?? '', true);
        $homeSections = $defaultSections;

        if (is_array($storedSections)) {
            foreach ($defaultSections as $key => $defaultSection) {
                $storedSection = $storedSections[$key] ?? null;

                if (
                    is_array($storedSection)
                    && is_bool($storedSection['enabled'] ?? null)
                    && is_int($storedSection['order'] ?? null)
                    && $storedSection['order'] >= 1
                    && $storedSection['order'] <= count($defaultSections)
                ) {
                    $homeSections[$key] = $storedSection;
                }
            }
        }

        uasort($homeSections, fn (array $left, array $right): int => $left['order'] <=> $right['order']);

        $bestsellers = Product::query()
            ->published()
            ->select($cardColumns)
            ->with(['category:id,name,slug', 'images' => $imageLoader])
            ->where('is_bestseller', true)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(4)
            ->get();

        $products = Product::query()
            ->published()
            ->select($cardColumns)
            ->with(['category:id,name,slug', 'images' => $imageLoader])
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $articles = Article::query()
            ->published()
            ->select(['id', 'author_id', 'title', 'slug', 'summary', 'image_path', 'image_alt', 'published_at', 'meta_title', 'meta_description'])
            ->with('author:id,name')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(3)
            ->get();
        $banner = Banner::query()
            ->where('is_active', true)
            ->where(fn (Builder $query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->first();

        return view('home', [
            'banner' => $banner,
            'siteName' => $siteName,
            'featuredProduct' => $featuredProduct,
            'categories' => $categories,
            'services' => $services,
            'bestsellers' => $bestsellers,
            'products' => $products,
            'articles' => $articles,
            'homeSections' => array_filter($homeSections, fn (array $section): bool => $section['enabled']),
            'homeCopy' => $organizationSettings,
            'isPreview' => $isPreview,
            'organizationStructuredData' => array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                'name' => $siteName,
                'url' => route('home'),
                'description' => $organizationSettings['profile_description'] ?? null,
                'email' => $organizationSettings['contact_email'] ?? null,
                'telephone' => $organizationSettings['contact_phone'] ?? null,
            ], static fn (mixed $value): bool => $value !== null && $value !== ''),
        ]);
    }
}
