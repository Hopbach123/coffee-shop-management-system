<?php

namespace Tests\Feature;

use App\Services\ScheduledPickupService;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ScheduledPickupTimeTest extends TestCase
{
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_local_day_boundary_is_persisted_as_utc_using_configured_hours(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-17 16:00:00', 'UTC'));
        config(['shop.opening_time' => '00:00', 'shop.closing_time' => '23:00']);
        $time = app(ScheduledPickupService::class)->validateTime('2026-11-18T00:30');
        $this->assertSame('2026-11-17 17:30:00', $time->format('Y-m-d H:i:s'));
        $this->assertSame('UTC', $time->timezoneName);
    }

    public function test_closing_boundary_is_rejected(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-17 00:00:00', 'UTC'));
        config(['shop.opening_time' => '09:00', 'shop.closing_time' => '18:00']);
        $this->expectException(ValidationException::class);
        app(ScheduledPickupService::class)->validateTime('2026-11-18T18:00');
    }

    public function test_missing_store_hours_fails_closed(): void
    {
        config(['shop.opening_time' => null, 'shop.closing_time' => null]);
        $this->expectException(ValidationException::class);
        app(ScheduledPickupService::class)->validateTime(now('Asia/Ho_Chi_Minh')->addDay()->format('Y-m-d\TH:i'));
    }
}
