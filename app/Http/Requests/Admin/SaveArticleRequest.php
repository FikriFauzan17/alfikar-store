<?php

namespace App\Http\Requests\Admin;

use App\Enums\ArticleStatus;
use App\Models\Article;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveArticleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('access-admin') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $attributes = [];
        $title = $this->input('title');
        $slug = $this->input('slug');
        $article = $this->route('article');

        if (is_string($title) && (! is_string($slug) || trim($slug) === '')) {
            $attributes['slug'] = $article instanceof Article ? $article->slug : Str::slug($title);
        } elseif (is_string($slug)) {
            $attributes['slug'] = Str::slug($slug);
        }

        if (! $this->exists('image_alt')) {
            $attributes['image_alt'] = null;
        }

        if (! $this->exists('remove_image')) {
            $attributes['remove_image'] = false;
        }

        if ($this->input('publication_action') === 'draft') {
            $attributes['status'] = ArticleStatus::Draft->value;
            $attributes['published_at'] = null;
        } elseif ($this->input('publication_action') === 'publish') {
            $attributes['status'] = ArticleStatus::Published->value;
        }

        $this->merge($attributes);
    }

    protected function getRedirectUrl(): string
    {
        $article = $this->route('article');

        return $article instanceof Article
            ? route('admin.articles.edit', $article)
            : route('admin.articles.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $article = $this->route('article');
        $slugRule = Rule::unique('articles', 'slug');
        $redirectSlugRule = Rule::unique('article_slug_redirects', 'slug');

        if ($article instanceof Article) {
            $slugRule->ignore($article->getKey());
            $redirectSlugRule->ignore($article->getKey(), 'article_id');
        }

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/',
                $slugRule,
                $redirectSlugRule,
            ],
            'summary' => ['required', 'string', 'max:5000'],
            'body' => ['required', 'string', 'max:50000'],
            'status' => ['required', Rule::enum(ArticleStatus::class)],
            'published_at' => ['nullable', 'date'],
            'publication_action' => ['nullable', Rule::in(['draft', 'publish'])],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'image_alt' => ['nullable', 'string', 'max:255'],
            'remove_image' => ['required', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:320'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['status', 'published_at'])) {
                    return;
                }

                $status = $this->input('status');
                $publishedAt = $this->date('published_at');

                if ($status === ArticleStatus::Scheduled->value && ($publishedAt === null || $publishedAt->isPast())) {
                    $validator->errors()->add('published_at', 'Jadwal publikasi harus di masa mendatang.');
                }

                if ($status === ArticleStatus::Published->value && $publishedAt?->isFuture()) {
                    $validator->errors()->add('published_at', 'Gunakan status terjadwal untuk waktu publikasi mendatang.');
                }

                if ($status === ArticleStatus::Draft->value && $publishedAt !== null) {
                    $validator->errors()->add('published_at', 'Draft tidak memiliki waktu publikasi.');
                }
            },
        ];
    }
}
