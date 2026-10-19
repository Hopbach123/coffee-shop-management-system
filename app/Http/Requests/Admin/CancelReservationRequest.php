<?php

namespace App\Http\Requests\Admin;

use App\Services\ReservationCancellationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CancelReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app()->environment(['local', 'testing']);
    }

    public function rules(): array
    {
        return ['expected_status' => ['required', 'string', Rule::in(ReservationCancellationService::CANCELLABLE_STATUSES)]];
    }

    public function messages(): array
    {
        return ['expected_status.required' => 'Vui lòng tải lại trang trước khi hủy.', 'expected_status.string' => 'Trạng thái yêu cầu không hợp lệ.', 'expected_status.in' => 'Trạng thái yêu cầu không hợp lệ.'];
    }
}
