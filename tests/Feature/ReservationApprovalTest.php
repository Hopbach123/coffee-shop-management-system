<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\CafeTable;
use App\Models\Reservation;
use App\Models\User;
use App\Services\ReservationApprovalService;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ReservationApprovalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        foreach (['0001_01_01_000000_create_users_table.php', '2026_08_14_000001_create_customers_table.php', '2026_08_14_000002_create_areas_table.php', '2026_08_14_000006_create_cafe_tables_table.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        // Existing reservations schema fixture; MySQL CHECK ALTER is not portable to SQLite.
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('table_id')->nullable()->constrained('cafe_tables')->restrictOnDelete();
            $table->string('customer_name');
            $table->string('customer_phone', 20);
            $table->integer('guest_count');
            $table->dateTime('reservation_start_at');
            $table->dateTime('reservation_end_at');
            $table->text('note')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'arrived', 'completed', 'cancelled', 'no_show'])->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
        $this->withoutVite();
        $this->travelTo(now()->setDate(2026, 10, 19)->setTime(10, 0));
    }

    private function fixtures(array $attributes = []): array
    {
        $admin = User::forceCreate(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'test-only-password', 'role' => 'admin', 'status' => 'active']);
        $this->actingAs($admin, 'web');
        $area = Area::create(['name' => 'Floor']);
        $table = CafeTable::create(['area_id' => $area->id, 'table_code' => 'T1', 'capacity' => 4]);
        $reservation = Reservation::forceCreate(array_merge(['table_id' => $table->id, 'customer_name' => 'Guest', 'customer_phone' => '0900000000', 'guest_count' => 2,
            'reservation_start_at' => '2026-10-20 12:00:00', 'reservation_end_at' => '2026-10-20 14:00:00', 'status' => 'pending', 'created_by' => $admin->id], $attributes));

        return [$admin, $area, $table, $reservation];
    }

    public function test_approval_excludes_self_changes_only_status_and_updated_at_and_renders_feedback(): void
    {
        [, , $table, $reservation] = $this->fixtures();
        $before = $reservation->fresh()->getAttributes();
        $tableBefore = $table->fresh()->getAttributes();
        $this->travel(1)->minutes();
        $this->get('/admin/reservations?status=pending')->assertOk()->assertSee(route('admin.reservations.approve', $reservation), false)->assertSee('name="_token"', false)->assertSee('name="_method" value="PATCH"', false)->assertSee('onsubmit="return confirm(', false);
        $this->get(route('admin.reservations.show', $reservation))->assertOk()->assertSee('Duyệt đặt bàn');
        DB::enableQueryLog();
        $this->patch(route('admin.reservations.approve', $reservation), ['status' => 'cancelled', 'created_by' => 999, 'guest_count' => 100, 'table_id' => 999])
            ->assertRedirect(route('admin.reservations.show', $reservation))->assertSessionHas('success', 'Đã xác nhận yêu cầu đặt bàn.');
        foreach (DB::getQueryLog() as $query) {
            if (preg_match('/^\s*(insert|update|delete|replace)\b/i', $query['query'])) {
                $this->assertMatchesRegularExpression('/^update "reservations" set "status" = \?, "updated_at" = \?/i', $query['query']);
            }
        }
        DB::disableQueryLog();
        DB::flushQueryLog();
        $after = $reservation->fresh()->getAttributes();
        $this->assertSame('confirmed', $after['status']);
        $this->assertNotSame($before['updated_at'], $after['updated_at']);
        unset($before['status'], $before['updated_at'], $after['status'], $after['updated_at']);
        $this->assertSame($before, $after);
        $this->assertSame($tableBefore, $table->fresh()->getAttributes());
        $this->get(route('admin.reservations.show', $reservation))->assertOk()->assertSee('Đã xác nhận')->assertDontSee('Duyệt đặt bàn');
        $this->get('/admin/reservations?status=pending')->assertOk()->assertDontSee('Guest');
    }

    public function test_all_non_pending_states_are_rejected_including_double_approval(): void
    {
        [, , , $reservation] = $this->fixtures();
        foreach (['confirmed', 'arrived', 'completed', 'cancelled', 'no_show'] as $status) {
            $reservation->forceFill(['status' => $status])->save();
            $this->patchJson(route('admin.reservations.approve', $reservation))->assertUnprocessable()->assertJsonValidationErrors('approval');
            $this->assertSame($status, $reservation->fresh()->status);
            $this->get(route('admin.reservations.show', $reservation))->assertOk()->assertDontSee('Duyệt đặt bàn');
        }
        $this->patch('/admin/reservations/99999/approve')->assertNotFound();
        $this->get(route('admin.reservations.approve', $reservation))->assertMethodNotAllowed();
    }

    public function test_master_changes_and_invalid_time_or_guests_reject_approval(): void
    {
        [, $area, $table, $reservation] = $this->fixtures();
        $table->forceFill(['capacity' => 1])->save();
        $this->patchJson(route('admin.reservations.approve', $reservation))->assertUnprocessable()->assertJsonValidationErrors('guest_count');
        $table->forceFill(['capacity' => 4, 'status' => 'maintenance'])->save();
        $this->patchJson(route('admin.reservations.approve', $reservation))->assertUnprocessable()->assertJsonValidationErrors('table_id');
        $table->forceFill(['status' => 'occupied'])->save();
        $area->forceFill(['status' => 'inactive'])->save();
        $this->patchJson(route('admin.reservations.approve', $reservation))->assertUnprocessable()->assertJsonValidationErrors('table_id');
        $area->forceFill(['status' => 'active'])->save();
        foreach ([['guest_count' => 0], ['reservation_start_at' => '2026-10-18 12:00:00'], ['reservation_end_at' => '2026-10-20 11:00:00']] as $changes) {
            $original = $reservation->fresh()->getAttributes();
            $reservation->forceFill($changes)->save();
            $this->patchJson(route('admin.reservations.approve', $reservation))->assertUnprocessable();
            $reservation->forceFill($original)->save();
        }
        $table->delete();
        $this->patchJson(route('admin.reservations.approve', $reservation))->assertUnprocessable()->assertJsonValidationErrors('table_id');
        $reservation->forceFill(['table_id' => null])->save();
        $this->patchJson(route('admin.reservations.approve', $reservation))->assertUnprocessable()->assertJsonValidationErrors('table_id');
        $this->assertSame('pending', $reservation->fresh()->status);
    }

    public function test_conflicts_block_including_other_pending_without_disclosing_contacts(): void
    {
        [, , , $reservation] = $this->fixtures();
        $other = $reservation->replicate();
        $other->customer_name = 'Private other guest';
        $other->save();
        foreach (['pending', 'confirmed', 'arrived'] as $status) {
            $other->forceFill(['status' => $status])->save();
            $this->patchJson(route('admin.reservations.approve', $reservation))->assertUnprocessable()->assertJsonValidationErrors('table_id')->assertDontSee('Private other guest');
            $this->assertSame('pending', $reservation->fresh()->status);
        }
        $this->from(route('admin.reservations.show', $reservation))->followingRedirects()->patch(route('admin.reservations.approve', $reservation))
            ->assertOk()->assertSee('Bàn đã có lịch đặt trùng thời gian.')->assertSee('Chờ duyệt');
        $this->assertSame('arrived', $other->fresh()->status);
    }

    public function test_failed_storage_rolls_back_and_does_not_report_success(): void
    {
        [, , , $reservation] = $this->fixtures();
        Reservation::saving(fn () => throw new QueryException('sqlite', 'private SQL', [], new \RuntimeException('private failure')));
        try {
            $this->followingRedirects()->patch(route('admin.reservations.approve', $reservation))->assertOk()->assertSee('Không thể duyệt đặt bàn do lỗi lưu trữ.')->assertDontSee('private SQL')->assertDontSee('Đã xác nhận yêu cầu đặt bàn.');
            $this->assertSame('pending', $reservation->fresh()->status);
        } finally {
            Reservation::flushEventListeners();
        }
    }

    public function test_guest_staff_and_non_development_environments_cannot_approve(): void
    {
        [$admin, , , $reservation] = $this->fixtures();
        $url = route('admin.reservations.approve', $reservation);
        $this->app['auth']->forgetGuards();
        $this->patch($url)->assertRedirect(route('login'));
        $admin->forceFill(['role' => 'staff'])->save();
        $this->actingAs($admin, 'web')->patch($url)->assertForbidden();
        $admin->forceFill(['role' => 'admin'])->save();
        $this->actingAs($admin, 'web');
        $this->app->instance('env', 'production');
        $this->withSession(['_token' => 'test-csrf'])->patch($url, ['_token' => 'test-csrf'])->assertForbidden();
        $this->get(route('admin.reservations.show', $reservation))->assertOk()->assertDontSee('Duyệt đặt bàn');
        $this->app->instance('env', 'testing');
        $this->assertSame('pending', $reservation->fresh()->status);
    }

    public function test_stale_table_assignment_fails_without_using_old_schedule(): void
    {
        [, $area, , $reservation] = $this->fixtures();
        $other = CafeTable::create(['area_id' => $area->id, 'table_code' => 'T2', 'capacity' => 4]);
        Reservation::whereKey($reservation->id)->update(['table_id' => $other->id]);
        try {
            app(ReservationApprovalService::class)->approve($reservation);
            $this->fail('Stale table must not be approved.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('approval', $exception->errors());
        }
        $this->assertSame('pending', $reservation->fresh()->status);
    }
}
