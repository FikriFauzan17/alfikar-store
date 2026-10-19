<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateProductImageOrderRequest extends FormRequest
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
            $imageOrder = $this->input('image_order', []);

            if (! $product instanceof Product || ! is_array($imageOrder)) {
                return;
            }

            $actualIds = $product->images()->pluck('id')->map(fn($id): int => (int) $id)->sort()->values()->all();
            $providedIds = array_map('intval', array_keys($imageOrder));
            sort($providedIds);

            if ($providedIds !== $actualIds) {
                $validator->errors()->add('image_order', 'Foto harus berasal dari produk ini.');

                return;
            }

            $positions = array_map('intval', array_values($imageOrder));
            sort($positions);

            if ($positions !== range(0, count($actualIds) - 1)) {
                $validator->errors()->add('image_order', 'Urutan foto harus unik dan berurutan.');
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
        return [
            'image_order' => ['required', 'array', 'min:1', 'max:5'],
            'image_order.*' => ['required', 'integer', 'distinct', 'min:0', 'max:4'],
        ];
    }
}
