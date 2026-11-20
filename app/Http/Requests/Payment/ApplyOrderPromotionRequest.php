<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class ApplyOrderPromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('web')?->canOperateSales() ?? false;
    }

    public function rules(): array
    {
        return ['code' => ['required', 'string', 'max:50']];
    }
}
