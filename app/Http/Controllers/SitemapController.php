<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Product;
use App\Services\InformationalPageService;
use App\Services\PublicSiteSettings;
use DateTimeInterface;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SitemapController extends Controller
{
    public function __invoke(
        InformationalPageService $pages,
        PublicSiteSettings $settings,
    ): StreamedResponse {
        $staticUrls = [
            route('home'),
            route('products.index'),
            route('pages.about'),
            route('pages.contact'),
            route('articles.index'),
        ];
        foreach ($pages->navigationPages($settings) as $slug => $_title) {
            $staticUrls[] = route('pages.information', ['page' => $slug]);
        }

        return response()->stream(function () use ($staticUrls): void {
            echo '<?xml version="1.0" encoding="UTF-8"?>';
            echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

            foreach ($staticUrls as $url) {
                $this->writeUrl($url);
            }

            foreach (Product::query()->published()->select(['id', 'slug', 'updated_at'])->orderBy('id')->cursor() as $product) {
                $this->writeUrl(route('products.show', ['product' => $product->slug]), $product->updated_at);
            }

            foreach (Article::query()->published()->select(['id', 'slug', 'updated_at'])->orderBy('id')->cursor() as $article) {
                $this->writeUrl(route('articles.show', ['article' => $article->slug]), $article->updated_at);
            }

            echo '</urlset>';
        }, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function robots(): Response
    {
        return response(
            "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /produk?\nSitemap: ".route('sitemap')."\n",
            200,
            [
                'Content-Type' => 'text/plain; charset=UTF-8',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }

    private function writeUrl(string $url, ?DateTimeInterface $lastModified = null): void
    {
        echo '<url><loc>'.htmlspecialchars($url, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</loc>';

        if ($lastModified !== null) {
            echo '<lastmod>'.$lastModified->format(DateTimeInterface::ATOM).'</lastmod>';
        }

        echo '</url>';
    }
}
