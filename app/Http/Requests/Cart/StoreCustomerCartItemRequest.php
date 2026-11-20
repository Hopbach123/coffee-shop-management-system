<?php

namespace App\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCustomerCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('note'))) {
            $this->merge(['note' => trim($this->input('note'))]);
        }
    }

    public function rules(): array
    {
        return [
            'variant_id' => ['required', 'integer', 'min:1'],
            'quantity' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'note' => ['nullable', 'string', 'max:500'],
            'toppings' => ['sometimes', 'array'],
            // Zero means this optional topping was not selected, never a saved quantity.
            'toppings.*' => ['required', 'integer', 'min:0', 'max:2147483647'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! is_array($this->input('toppings'))) {
                return;
            }

            foreach (array_keys($this->input('toppings')) as $id) {
                if (! preg_match('/^[1-9][0-9]*$/', (string) $id)
                    || filter_var($id, FILTER_VALIDATE_INT) === false) {
                    $validator->errors()->add('toppings', 'Mã topping không hợp lệ.');
                    break;
                }
            }
        }];
    }

    public function attributes(): array
    {
        return ['variant_id' => 'kích cỡ món', 'quantity' => 'số lượng món',
            'note' => 'ghi chú', 'toppings' => 'topping', 'toppings.*' => 'số lượng topping'];
    }
}
