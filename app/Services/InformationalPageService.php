<?php

namespace App\Services;

use App\Models\SiteSetting;

class InformationalPageService
{
    private const PAGES = [
        'kebijakan-privasi' => [
            'key' => 'privacy_policy',
            'title' => 'Kebijakan Privasi',
            'draft_notice' => 'Draf kebijakan privasi belum diberikan. Lengkapi dengan kebijakan yang telah disetujui sebelum dipublikasikan.',
        ],
        'ketentuan-pemesanan' => [
            'key' => 'ordering_terms',
            'title' => 'Ketentuan Pemesanan',
            'draft_notice' => 'Draf ketentuan pemesanan belum diberikan. Lengkapi dengan ketentuan yang telah disetujui sebelum dipublikasikan.',
        ],
    ];

    /**
     * @return array{key: string, title: string, draft_notice: string}|null
     */
    public function definition(string $slug): ?array
    {
        return self::PAGES[$slug] ?? null;
    }

    /**
     * @return array{title: string, content: string}
     */
    public function defaultContent(string $slug): array
    {
        $definition = $this->definition($slug);

        if ($definition === null) {
            abort(404);
        }

        return [
            'title' => $definition['title'],
            'content' => '',
        ];
    }

    /**
     * @return array{title: string, content: string}|null
     */
    public function decode(?string $value): ?array
    {
        if ($value === null) {
            return null;
        }

        $decoded = json_decode($value, true);

        if (! is_array($decoded) || ! is_string($decoded['title'] ?? null) || ! is_string($decoded['content'] ?? null)) {
            return null;
        }

        return [
            'title' => $decoded['title'],
            'content' => $decoded['content'],
        ];
    }

    /**
     * @return array{title: string, content: string}|null
     */
    public function published(string $slug, PublicSiteSettings $settings): ?array
    {
        $definition = $this->definition($slug);

        if ($definition === null) {
            return null;
        }

        return $this->decode($settings->values([$definition['key']])[$definition['key']] ?? null);
    }

    /**
     * @return array{title: string, content: string, is_published: bool, has_draft: bool}
     */
    public function adminContent(string $slug, PublicSiteSettings $settings): array
    {
        $definition = $this->definition($slug);

        if ($definition === null) {
            abort(404);
        }

        $draft = SiteSetting::query()
            ->where('setting_key', 'draft_'.$definition['key'])
            ->where('is_public', false)
            ->value('setting_value');
        $published = $this->published($slug, $settings);
        $hasDraft = is_string($draft) && $this->decode($draft) !== null;
        $content = $this->decode(is_string($draft) ? $draft : null)
            ?? $published
            ?? $this->defaultContent($slug);

        return [...$content, 'is_published' => $published !== null, 'has_draft' => $hasDraft];
    }

    /**
     * @return array<string, string>
     */
    public function navigationPages(PublicSiteSettings $settings): array
    {
        $pages = [];

        foreach (self::PAGES as $slug => $definition) {
            if ($this->published($slug, $settings) !== null) {
                $pages[$slug] = $definition['title'];
            }
        }

        return $pages;
    }
}
