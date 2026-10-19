<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveProductRequest extends FormRequest
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
        $code = $this->input('code');
        $title = $this->input('title');
        $slug = $this->input('slug');
        $product = $this->route('product');

        if (is_string($code)) {
            $attributes['code'] = Str::upper(trim($code));
        }

        if (is_string($title) && (! is_string($slug) || trim($slug) === '')) {
            $attributes['slug'] = $product instanceof Product ? $product->slug : Str::slug($title);
        } elseif (is_string($slug)) {
            $attributes['slug'] = Str::slug($slug);
        }

        foreach (['is_published', 'is_featured', 'is_bestseller'] as $booleanField) {
            if (! $this->exists($booleanField)) {
                $attributes[$booleanField] = false;
            }
        }

        $this->merge($attributes);
    }

    protected function getRedirectUrl(): string
    {
        $product = $this->route('product');

        return $product instanceof Product
            ? route('admin.products.edit', $product)
            : route('admin.products.create');
    }

    public function productAttributes(): array
    {
        $attributes = $this->validated();
        $publicationAction = $attributes['publication_action'] ?? null;
        unset($attributes['publication_action']);
        $specificationsJson = $attributes['specifications_json'] ?? null;
        unset($attributes['specifications_json']);

        $attributes['specifications'] = is_string($specificationsJson) && trim($specificationsJson) !== ''
            ? json_decode($specificationsJson, true, 512, JSON_THROW_ON_ERROR)
            : null;
        $attributes['is_published'] = $this->boolean('is_published');
        $attributes['is_featured'] = $this->boolean('is_featured');
        $attributes['is_bestseller'] = $this->boolean('is_bestseller');

        if ($publicationAction === 'draft') {
            $attributes['is_published'] = false;
            $attributes['published_at'] = null;
        } elseif ($publicationAction === 'publish') {
            $attributes['is_published'] = true;
        }

        return $attributes;
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $specificationsJson = $this->input('specifications_json');

            if (
                ! $validator->errors()->has('specifications_json')
                && is_string($specificationsJson)
                && trim($specificationsJson) !== ''
            ) {
                $decoded = json_decode($specificationsJson, true);

                if (! is_array($decoded) || count($decoded) > 50) {
                    $validator->errors()->add('specifications_json', 'Spesifikasi harus berupa objek atau daftar JSON.');

                    return;
                }

                foreach ($decoded as $key => $value) {
                    if (! is_scalar($value) || strlen((string) $key) > 80 || strlen((string) $value) > 500) {
                        $validator->errors()->add('specifications_json', 'Setiap spesifikasi harus berupa teks singkat.');

                        return;
                    }
                }
            }

            $wholesalePrice = $this->input('wholesale_price');
            $unitPrice = $this->input('unit_price');

            if (
                ! $validator->errors()->hasAny(['unit_price', 'wholesale_price'])
                && is_numeric($wholesalePrice)
                && is_numeric($unitPrice)
                && (float) $wholesalePrice > (float) $unitPrice
            ) {
                $validator->errors()->add('wholesale_price', 'Harga grosir tidak boleh melebihi harga satuan.');
            }
        }];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        $product = $this->route('product');
        $uniqueCode = Rule::unique('products', 'code');
        $slugConstraint = Rule::unique('products', 'slug');
        $redirectSlugConstraint = Rule::unique('product_slug_redirects', 'slug');
        $uniqueIsbn = Rule::unique('products', 'isbn');

        if ($product instanceof Product) {
            $uniqueCode->ignore($product->getKey());
            $slugConstraint->ignore($product->getKey());
            $redirectSlugConstraint->ignore($product->getKey(), 'product_id');
            $uniqueIsbn->ignore($product->getKey());
        }

        return [
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'code' => ['required', 'string', 'max:100', $uniqueCode],
            'slug' => ['required', 'string', 'max:255', 'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $slugConstraint, $redirectSlugConstraint],
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'publisher' => ['required', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', 'max:255', $uniqueIsbn],
            'description' => ['required', 'string', 'max:50000'],
            'specifications_json' => ['nullable', 'string', 'max:10000', 'json'],
            'stock' => $product instanceof Product
                ? ['exclude']
                : ['required', 'integer', 'min:0', 'max:4294967295'],
            'unit_price' => ['required', 'integer', 'min:0', 'max:9223372036854775807'],
            'wholesale_price' => ['nullable', 'integer', 'min:0', 'max:9223372036854775807', Rule::requiredIf($this->filled('wholesale_minimum'))],
            'wholesale_minimum' => ['nullable', 'integer', 'min:1', 'max:4294967295', Rule::requiredIf($this->filled('wholesale_price'))],
            'is_published' => ['required', 'boolean'],
            'is_featured' => ['required', 'boolean'],
            'is_bestseller' => ['required', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'publication_action' => ['nullable', Rule::in(['draft', 'publish'])],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:320'],
        ];
    }
}
