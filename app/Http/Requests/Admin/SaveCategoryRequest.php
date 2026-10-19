<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SaveCategoryRequest extends FormRequest
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
        $name = $this->input('name');
        $slug = $this->input('slug');
        $category = $this->route('category');

        if (is_string($name) && (! is_string($slug) || trim($slug) === '')) {
            $attributes['slug'] = $category instanceof Category ? $category->slug : Str::slug($name);
        } elseif (is_string($slug)) {
            $attributes['slug'] = Str::slug($slug);
        }

        if (! $this->exists('is_published')) {
            $attributes['is_published'] = false;
        }

        $this->merge($attributes);
    }

    protected function getRedirectUrl(): string
    {
        $category = $this->route('category');

        return $category instanceof Category
            ? route('admin.categories.edit', $category)
            : route('admin.categories.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        $category = $this->route('category');
        $slugConstraint = $category instanceof Category
            ? Rule::in([$category->slug])
            : Rule::unique('categories', 'slug');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $slugConstraint],
            'description' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
            'remove_image' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_published' => ['required', 'boolean'],
        ];
    }
}
