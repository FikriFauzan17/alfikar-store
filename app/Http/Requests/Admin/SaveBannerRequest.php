<?php

namespace App\Http\Requests\Admin;

use App\Models\Banner;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SaveBannerRequest extends FormRequest
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
        if (! $this->exists('is_active')) {
            $this->merge(['is_active' => false]);
        }

        if (! $this->exists('remove_image')) {
            $this->merge(['remove_image' => false]);
        }
    }

    protected function getRedirectUrl(): string
    {
        $banner = $this->route('banner');

        return $banner instanceof Banner
            ? route('admin.banners.edit', $banner)
            : route('admin.banners.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'eyebrow' => ['nullable', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'image_alt' => ['nullable', 'string', 'max:255'],
            'remove_image' => ['required', 'boolean'],
            'link_url' => ['nullable', 'string', 'max:2048'],
            'link_label' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('link_url') || ! filled($this->input('link_url'))) {
                    return;
                }

                $url = (string) $this->input('link_url');
                $isRelative = str_starts_with($url, '/') && ! str_starts_with($url, '//');
                $isHttps = filter_var($url, FILTER_VALIDATE_URL) !== false
                    && strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https';

                if (! $isRelative && ! $isHttps) {
                    $validator->errors()->add('link_url', 'Tautan banner harus berupa URL HTTPS atau path lokal.');
                }
            },
        ];
    }
}
