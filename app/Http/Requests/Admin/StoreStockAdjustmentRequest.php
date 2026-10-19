<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreStockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('access-admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'direction' => ['required', Rule::in(['increase', 'decrease'])],
            'reason' => ['required', Rule::in(['whatsapp_sale', 'goods_received', 'correction'])],
            'quantity' => ['required', 'integer', 'min:1', 'max:4294967295'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'submission_token' => ['required', 'uuid'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['direction', 'reason'])) {
                    return;
                }

                $expectedDirection = match ($this->input('reason')) {
                    'whatsapp_sale' => 'decrease',
                    'goods_received' => 'increase',
                    'correction' => null,
                };

                if ($expectedDirection !== null && $this->input('direction') !== $expectedDirection) {
                    $validator->errors()->add('direction', 'Arah penyesuaian tidak sesuai dengan alasan yang dipilih.');
                }
            },
        ];
    }
}
