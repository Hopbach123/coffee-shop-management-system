<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Reservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservationCreationService
{
    public function __construct(private ReservationAvailabilityService $availability) {}

    public function create(array $attributes, User $actor): Reservation
    {
        abort_unless(app()->environment(['local', 'testing']), 403);

        return DB::transaction(function () use ($attributes, $actor): Reservation {
            $start = CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $attributes['reservation_start_at'], config('app.timezone'));
            $end = CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $attributes['reservation_end_at'], config('app.timezone'));
            $this->availability->assertAvailable((int) $attributes['table_id'], (int) $attributes['guest_count'], $start, $end);
            if (! empty($attributes['customer_id'])) {
                $customer = Customer::query()->lockForUpdate()->find($attributes['customer_id']);
                if ($customer === null || $customer->status !== 'active') {
                    throw ValidationException::withMessages(['customer_id' => 'Khách hàng không còn hoạt động hoặc đã xóa.']);
                }
            }
            // Customer lookup can also wait for a lock after availability has passed.
            if ($start->lessThanOrEqualTo(now())) {
                throw ValidationException::withMessages(['reservation_start_at' => 'Thời gian bắt đầu phải ở tương lai.']);
            }
            $reservation = new Reservation;
            $reservation->fill(array_intersect_key($attributes, array_flip(['customer_id', 'table_id', 'customer_name', 'customer_phone', 'guest_count', 'note'])));
            $reservation->reservation_start_at = $start;
            $reservation->reservation_end_at = $end;
            $reservation->status = 'pending';
            $reservation->created_by = $actor->getKey();
            $reservation->saveOrFail();

            return $reservation;
        }, 3);
    }
}
