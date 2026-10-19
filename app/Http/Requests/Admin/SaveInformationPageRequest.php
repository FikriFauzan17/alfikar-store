<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveInformationPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('access-admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:30000'],
            'action' => ['required', Rule::in(['draft', 'publish'])],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('action') === 'publish' && ! filled($this->input('content'))) {
                    $validator->errors()->add('content', 'Isi halaman perlu dilengkapi sebelum dipublikasikan.');
                }
            },
        ];
    }
}
