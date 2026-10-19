<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveArticleRequest;
use App\Models\Article;
use App\Models\ArticleSlugRedirect;
use App\Services\AdminAuditLogger;
use App\Services\ArticleHtmlSanitizer;
use App\Services\ProductImageProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ArticleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $articles = Article::query()
            ->with('author:id,name')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.articles.index', ['articles' => $articles]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('admin.articles.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(
        SaveArticleRequest $request,
        ArticleHtmlSanitizer $sanitizer,
        ProductImageProcessor $images,
        AdminAuditLogger $audit,
    ): RedirectResponse {
        $attributes = $this->attributes($request->validated(), $sanitizer);
        $imagePath = null;
        $articleId = null;

        try {
            $article = DB::transaction(function () use ($request, $attributes, $images, $audit, &$imagePath, &$articleId): Article {
                $article = Article::query()->create([
                    ...$attributes,
                    'author_id' => $request->user()->getAuthIdentifier(),
                ]);
                $articleId = (int) $article->getKey();

                if ($request->hasFile('image')) {
                    $imagePath = $images->storeContentImage('articles/'.$article->getKey(), $request->file('image'));
                    $article->image_path = $imagePath;
                    $article->image_alt = $request->validated('image_alt');
                    $article->save();
                }

                $audit->record($request->user(), 'article.created', $article);

                return $article;
            });
        } catch (\Throwable $exception) {
            if ($imagePath !== null && $articleId !== null) {
                $images->deleteContentImage('articles/'.$articleId, $imagePath);
            }

            throw $exception;
        }

        return redirect()->route('admin.articles.edit', $article)->with('status', 'Artikel berhasil dibuat.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Article $article): never
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Article $article): View
    {
        return view('admin.articles.edit', ['article' => $article]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        SaveArticleRequest $request,
        Article $article,
        ArticleHtmlSanitizer $sanitizer,
        ProductImageProcessor $images,
        AdminAuditLogger $audit,
    ): RedirectResponse {
        $attributes = $this->attributes($request->validated(), $sanitizer);
        $previousImagePath = $article->image_path;
        $newImagePath = null;

        try {
            DB::transaction(function () use ($request, $article, $attributes, $images, $audit, &$newImagePath): void {
                $lockedArticle = Article::query()->whereKey($article->getKey())->lockForUpdate()->firstOrFail();
                $previousSlug = $lockedArticle->slug;
                ArticleSlugRedirect::query()
                    ->where('article_id', $lockedArticle->getKey())
                    ->where('slug', $attributes['slug'])
                    ->delete();
                $lockedArticle->fill($attributes);

                if ($request->boolean('remove_image')) {
                    $lockedArticle->image_path = null;
                    $lockedArticle->image_alt = null;
                }

                if ($request->hasFile('image')) {
                    $newImagePath = $images->storeContentImage('articles/'.$lockedArticle->getKey(), $request->file('image'));
                    $lockedArticle->image_path = $newImagePath;
                    $lockedArticle->image_alt = $request->validated('image_alt');
                } elseif ($lockedArticle->image_path !== null && ! $request->boolean('remove_image')) {
                    $lockedArticle->image_alt = $request->validated('image_alt');
                }

                $lockedArticle->save();

                if ($previousSlug !== $lockedArticle->slug) {
                    ArticleSlugRedirect::query()->updateOrCreate(
                        ['slug' => $previousSlug],
                        ['article_id' => $lockedArticle->getKey()],
                    );
                }

                $audit->record($request->user(), 'article.updated', $lockedArticle);
            });
        } catch (\Throwable $exception) {
            if ($newImagePath !== null) {
                $images->deleteContentImage('articles/'.$article->getKey(), $newImagePath);
            }

            throw $exception;
        }

        if (($newImagePath !== null || $request->boolean('remove_image')) && $previousImagePath !== null) {
            $images->deleteContentImage('articles/'.$article->getKey(), $previousImagePath);
        }

        return redirect()->route('admin.articles.edit', $article)->with('status', 'Artikel berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function confirmDelete(Article $article): View
    {
        return view('admin.articles.confirm-delete', ['article' => $article]);
    }

    public function destroy(
        Article $article,
        Request $request,
        ProductImageProcessor $images,
        AdminAuditLogger $audit,
    ): RedirectResponse {
        $imagePath = $article->image_path;
        $articleId = $article->getKey();

        DB::transaction(function () use ($article, $request, $audit): void {
            $article->delete();
            $audit->record($request->user(), 'article.deleted', $article);
        });

        if ($imagePath !== null) {
            $images->deleteContentImage('articles/'.$articleId, $imagePath);
        }

        return redirect()->route('admin.articles.index')->with('status', 'Artikel berhasil dihapus.');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function attributes(array $validated, ArticleHtmlSanitizer $sanitizer): array
    {
        $status = $validated['status'];
        $publishedAt = match ($status) {
            'draft' => null,
            'published' => $validated['published_at'] ?? now(),
            'scheduled' => $validated['published_at'],
        };

        return [
            'title' => $validated['title'],
            'slug' => $validated['slug'],
            'summary' => $validated['summary'],
            'body' => $sanitizer->sanitize($validated['body']),
            'status' => $status,
            'published_at' => $publishedAt,
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
        ];
    }
}
