<?php

namespace App\Http\Requests\Admin;

use App\Services\ArticleHtmlSanitizer;
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
                if ($validator->errors()->has('content')) {
                    return;
                }

                $content = $this->input('content');
                $plainContent = is_string($content)
                    ? preg_replace(
                        '/[\p{Z}\s]+/u',
                        '',
                        html_entity_decode(strip_tags(app(ArticleHtmlSanitizer::class)->sanitize($content)), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                    )
                    : null;

                if ($this->input('action') === 'publish' && (! is_string($plainContent) || $plainContent === '')) {
                    $validator->errors()->add('content', 'Isi halaman perlu dilengkapi sebelum dipublikasikan.');
                }
            },
        ];
    }
}
