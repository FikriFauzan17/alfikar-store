<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleSlugRedirect;
use App\Services\ArticleHtmlSanitizer;
use App\Services\PublicSiteSettings;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function index(PublicSiteSettings $settings): View
    {
        $articles = Article::query()
            ->published()
            ->select(['id', 'author_id', 'title', 'slug', 'summary', 'image_path', 'image_alt', 'published_at', 'meta_title', 'meta_description'])
            ->with('author:id,name')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(12);

        $page = $articles->currentPage();
        $pageParameter = request()->query('page');
        $isValidPage = $pageParameter === null
            || (filter_var($pageParameter, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false
                && (int) $pageParameter === $page);

        abort_if($page > $articles->lastPage(), 404);

        $isFiltered = array_diff(array_keys(request()->query()), ['page']) !== [] || ! $isValidPage;
        $articleListUrl = route('articles.index', $page > 1 ? ['page' => $page] : []);

        return view('articles.index', [
            'articles' => $articles,
            'siteName' => $settings->values(['profile_name'])['profile_name'] ?? 'Toko Buku Alfikar',
            'articleListUrl' => $articleListUrl,
            'robots' => $isFiltered ? 'noindex,follow' : 'index,follow',
            'breadcrumbStructuredData' => [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda', 'item' => route('home')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Artikel', 'item' => $articleListUrl],
                ],
            ],
        ]);
    }

    public function preview(
        Article $article,
        ArticleHtmlSanitizer $sanitizer,
        PublicSiteSettings $settings,
    ): Response {
        $article->load('author:id,name');
        $view = $this->articleView($article, $sanitizer, $settings, true);

        return response($view->render(), 200, [
            'X-Robots-Tag' => 'noindex, nofollow',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    public function show(
        string $article,
        ArticleHtmlSanitizer $sanitizer,
        PublicSiteSettings $settings,
    ): View|RedirectResponse {
        $requestedSlug = $article;
        $article = Article::query()
            ->published()
            ->with('author:id,name')
            ->where('slug', $requestedSlug)
            ->first();

        if ($article === null) {
            $slugRedirect = ArticleSlugRedirect::query()->where('slug', $requestedSlug)->first();
            $redirectTarget = $slugRedirect === null
                ? null
                : Article::query()->published()->whereKey($slugRedirect->article_id)->first(['id', 'slug']);

            if ($redirectTarget === null) {
                abort(404);
            }

            return redirect()->route('articles.show', ['article' => $redirectTarget->slug], 301);
        }

        return $this->articleView($article, $sanitizer, $settings);
    }

    private function articleView(
        Article $article,
        ArticleHtmlSanitizer $sanitizer,
        PublicSiteSettings $settings,
        bool $isPreview = false,
    ): View {
        $safeBody = $sanitizer->sanitize($article->body);

        $articleUrl = $isPreview
            ? request()->url()
            : route('articles.show', ['article' => $article->slug]);
        $siteName = $settings->values(['profile_name'])['profile_name'] ?? 'Toko Buku Alfikar';
        $articleStructuredData = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $article->title,
            'description' => $article->meta_description ?: $article->summary,
            'author' => $article->author ? ['@type' => 'Person', 'name' => $article->author->name] : null,
            'datePublished' => $article->published_at?->toIso8601String(),
            'dateModified' => $article->updated_at?->toIso8601String(),
            'image' => $article->image_path ? $this->publicImageUrl($article->image_path) : null,
            'mainEntityOfPage' => $articleUrl,
            'publisher' => ['@type' => 'Organization', 'name' => $siteName, 'url' => route('home')],
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
        $breadcrumbStructuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda', 'item' => route('home')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Artikel', 'item' => route('articles.index')],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $article->title, 'item' => $articleUrl],
            ],
        ];

        return view('articles.show', [
            'article' => $article,
            'siteName' => $siteName,
            'safeBody' => $safeBody,
            'isPreview' => $isPreview,
            'articleStructuredData' => $articleStructuredData,
            'breadcrumbStructuredData' => $breadcrumbStructuredData,
        ]);
    }

    private function publicImageUrl(string $path): string
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk((string) config('image-processing.disk', 'public'));

        return $disk->url($path);
    }
}
