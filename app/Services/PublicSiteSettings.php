<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

class PublicSiteSettings
{
    /**
     * @param  list<string>  $keys
     * @return array<string, string>
     */
    public function values(array $keys): array
    {
        $keys = array_values(array_unique(array_filter($keys, 'is_string')));

        if ($keys === []) {
            return [];
        }

        $values = Cache::remember('public-site-settings', now()->addMinutes(5), fn (): array => SiteSetting::query()
            ->where('is_public', true)
            ->pluck('setting_value', 'setting_key')
            ->map(fn ($value): string => (string) $value)
            ->all());

        return array_intersect_key($values, array_flip($keys));
    }

    public function whatsappNumber(): ?string
    {
        $number = SiteSetting::query()
            ->where('setting_key', 'whatsapp_number')
            ->where('is_public', true)
            ->value('setting_value');

        if (! is_string($number) || ! preg_match('/\A\+[1-9][0-9]{7,14}\z/', trim($number))) {
            return null;
        }

        return substr(trim($number), 1);
    }

    public function safeExternalUrl(?string $value): ?string
    {
        if (! is_string($value) || filter_var($value, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));

        return $scheme === 'https' ? $value : null;
    }

    public function safeMapUrl(?string $value): ?string
    {
        $url = $this->safeExternalUrl($value);
        $host = strtolower((string) parse_url((string) $url, PHP_URL_HOST));
        $path = strtolower((string) parse_url((string) $url, PHP_URL_PATH));

        if (
            $url === null
            || ! in_array($host, ['maps.google.com', 'www.google.com', 'google.com', 'maps.app.goo.gl'], true)
            || ($host !== 'maps.app.goo.gl' && ! str_starts_with($path, '/maps'))
        ) {
            return null;
        }

        return $url;
    }
}
