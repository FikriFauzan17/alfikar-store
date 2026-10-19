<?php

namespace App\Models;

use App\Enums\ArticleStatus;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @method static Builder published()
 */
#[Fillable(['author_id', 'title', 'slug', 'summary', 'body', 'image_path', 'image_alt', 'status', 'published_at', 'meta_title', 'meta_description'])]
class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory;

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    #[Scope]
    protected function published(Builder $query): void
    {
        $now = now();

        $query->where(function (Builder $query) use ($now): void {
            $query->where(function (Builder $query) use ($now): void {
                $query->where('status', ArticleStatus::Published)
                    ->where(function (Builder $query) use ($now): void {
                        $query->whereNull('published_at')
                            ->orWhere('published_at', '<=', $now);
                    });
            })->orWhere(function (Builder $query) use ($now): void {
                $query->where('status', ArticleStatus::Scheduled)
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', $now);
            });
        });
    }

    protected function casts(): array
    {
        return [
            'status' => ArticleStatus::class,
            'published_at' => 'datetime',
        ];
    }
}
