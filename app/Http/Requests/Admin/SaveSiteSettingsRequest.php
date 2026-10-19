<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SaveSiteSettingsRequest extends FormRequest
{
    private const HOME_SECTIONS = ['hero', 'categories', 'services', 'bestsellers', 'products', 'articles'];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('access-admin') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->exists('home_sections')) {
            $this->merge([
                'home_sections' => [
                    'hero' => ['enabled' => true, 'order' => 1],
                    'categories' => ['enabled' => true, 'order' => 2],
                    'services' => ['enabled' => true, 'order' => 3],
                    'bestsellers' => ['enabled' => true, 'order' => 4],
                    'products' => ['enabled' => true, 'order' => 5],
                    'articles' => ['enabled' => true, 'order' => 6],
                ],
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'profile_name' => ['nullable', 'string', 'max:255'],
            'profile_description' => ['nullable', 'string', 'max:10000'],
            'profile_address' => ['nullable', 'string', 'max:2000'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
            'remove_logo' => ['nullable', 'boolean'],
            'contact_address' => ['nullable', 'string', 'max:2000'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'whatsapp_number' => ['nullable', 'regex:/\A\+[1-9][0-9]{7,14}\z/'],
            'contact_map_url' => ['nullable', 'string', 'max:2048'],
            'social_instagram' => ['nullable', 'url:https', 'max:2048'],
            'social_facebook' => ['nullable', 'url:https', 'max:2048'],
            'social_youtube' => ['nullable', 'url:https', 'max:2048'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:320'],
            'service_delivery' => ['nullable', 'string', 'max:2000'],
            'service_payment' => ['nullable', 'string', 'max:2000'],
            'service_returns' => ['nullable', 'string', 'max:2000'],
            'service_hours' => ['nullable', 'string', 'max:2000'],
            'home_hero_title' => ['nullable', 'string', 'max:255'],
            'home_hero_description' => ['nullable', 'string', 'max:2000'],
            'home_hero_cta_label' => ['nullable', 'string', 'max:100'],
            'home_hero_cta_url' => ['nullable', 'string', 'max:2048'],
            'home_sections' => ['required', 'array:'.implode(',', self::HOME_SECTIONS)],
            ...collect(self::HOME_SECTIONS)->flatMap(fn (string $section): array => [
                "home_sections.{$section}" => ['required', 'array:enabled,order'],
                "home_sections.{$section}.enabled" => ['required', 'boolean'],
                "home_sections.{$section}.order" => ['required', 'integer', 'min:1', 'max:'.count(self::HOME_SECTIONS)],
            ])->all(),
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $validator->errors()->has('home_sections')) {
                    $orders = collect(self::HOME_SECTIONS)
                        ->map(fn (string $section): mixed => $this->input("home_sections.{$section}.order"))
                        ->filter(fn (mixed $order): bool => is_numeric($order))
                        ->map(fn (mixed $order): int => (int) $order);

                    if ($orders->count() === count(self::HOME_SECTIONS) && $orders->unique()->count() !== count(self::HOME_SECTIONS)) {
                        $validator->errors()->add('home_sections', 'Urutan setiap bagian beranda harus berbeda.');
                    }
                }

                if ($validator->errors()->has('home_hero_cta_url') || ! filled($this->input('home_hero_cta_url'))) {
                    return;
                }

                $url = (string) $this->input('home_hero_cta_url');
                $isRelative = preg_match('/\A\/(?!\/)[^\s\\\\]*\z/u', $url) === 1;
                $isHttps = filter_var($url, FILTER_VALIDATE_URL) !== false
                    && strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https'
                    && filled(parse_url($url, PHP_URL_HOST))
                    && parse_url($url, PHP_URL_USER) === null
                    && parse_url($url, PHP_URL_PASS) === null;

                if (! $isRelative && ! $isHttps) {
                    $validator->errors()->add('home_hero_cta_url', 'Tautan tombol hero harus berupa path lokal atau URL HTTPS yang valid.');
                }
            },
            function (Validator $validator): void {
                if ($validator->errors()->has('contact_map_url') || ! filled($this->input('contact_map_url'))) {
                    return;
                }

                $url = (string) $this->input('contact_map_url');
                $host = strtolower((string) parse_url($url, PHP_URL_HOST));
                $path = strtolower((string) parse_url($url, PHP_URL_PATH));
                $valid = filter_var($url, FILTER_VALIDATE_URL) !== false
                    && strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https'
                    && in_array($host, ['maps.google.com', 'www.google.com', 'google.com', 'maps.app.goo.gl'], true)
                    && ($host === 'maps.app.goo.gl' || str_starts_with($path, '/maps'));

                if (! $valid) {
                    $validator->errors()->add('contact_map_url', 'Gunakan tautan Google Maps HTTPS yang valid.');
                }
            },
        ];
    }
}
