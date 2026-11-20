<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class ScheduledPickupService
{
    public function validateTime(string $value): CarbonImmutable
    {
        // datetime-local is shop wall time; persisted DATETIME is always UTC.
        $time = CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $value, 'Asia/Ho_Chi_Minh');
        if (! $time || $time->format('Y-m-d\TH:i') !== $value || $time->isPast()) {
            throw ValidationException::withMessages(['pickup_at' => 'Giờ nhận phải là ngày giờ hợp lệ trong tương lai (giờ Việt Nam).']);
        }

        $open = config('shop.opening_time');
        $close = config('shop.closing_time');
        if (! is_string($open) || ! is_string($close)
            || ! preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $open)
            || ! preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $close) || $open >= $close) {
            throw ValidationException::withMessages(['pickup_at' => 'Quán chưa cấu hình giờ nhận đồ hợp lệ. Vui lòng liên hệ quán.']);
        }
        if ($time->format('H:i') < $open || $time->format('H:i') >= $close) {
            throw ValidationException::withMessages(['pickup_at' => "Giờ nhận phải trong giờ phục vụ {$open}–{$close} (giờ Việt Nam)."]);
        }

        return $time->utc();
    }
}
