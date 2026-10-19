<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreProductImagesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('access-admin') ?? false;
    }

    protected function getRedirectUrl(): string
    {
        $product = $this->route('product');

        return $product instanceof Product
            ? route('admin.products.edit', $product)
            : route('admin.products.index');
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $product = $this->route('product');
            $uploads = $this->file('images', []);
            $uploadCount = is_array($uploads) ? count($uploads) : ($uploads === null ? 0 : 1);

            if (
                $product instanceof Product
                && $product->images()->count() + $uploadCount > (int) config('image-processing.max_images', 5)
            ) {
                $validator->errors()->add('images', 'Produk maksimal memiliki lima foto.');
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
        $maxImageKilobytes = (int) ceil(((int) config('image-processing.max_upload_bytes', 5 * 1024 * 1024)) / 1024);

        return [
            'images' => ['required', 'array', 'min:1', 'max:5'],
            'images.*' => ['required', 'file', 'mimes:jpeg,jpg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:' . $maxImageKilobytes],
            'image_alt' => ['nullable', 'array', 'max:5'],
            'image_alt.*' => ['nullable', 'string', 'max:255'],
        ];
    }
}
