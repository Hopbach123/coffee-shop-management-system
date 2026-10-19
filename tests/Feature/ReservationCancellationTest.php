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

class ReservationCancellationTest extends TestCase
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

    private function orderFixture(): void
    {
        // Subset of the existing orders schema needed for read-only relationship tests.
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->nullable()->constrained('reservations')->restrictOnDelete();
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
        });
    }

    public function test_pending_and_confirmed_cancel_preserving_every_other_field_and_history(): void
    {
        [, , $table, $reservation] = $this->fixtures();
        $this->orderFixture();
        foreach (['pending', 'confirmed'] as $status) {
            $reservation->forceFill(['status' => $status, 'note' => 'Keep note', 'reservation_start_at' => '2026-10-18 10:00:00', 'reservation_end_at' => '2026-10-18 12:00:00'])->save();
            $before = $reservation->fresh()->getAttributes();
            $tableBefore = $table->fresh()->getAttributes();
            $this->travel(1)->minutes();
            $this->get(route('admin.reservations.show', $reservation))->assertOk()->assertSee('Hủy đặt bàn')->assertSee('name="expected_status" value="'.$status.'"', false)->assertSee('name="_token"', false)->assertSee('onsubmit="return confirm(', false);
            DB::enableQueryLog();
            $this->patch(route('admin.reservations.cancel', $reservation), ['expected_status' => $status, 'status' => 'completed', 'note' => 'Overwrite', 'table_id' => 999])
                ->assertRedirect(route('admin.reservations.show', $reservation))->assertSessionHas('success', 'Đã hủy yêu cầu đặt bàn.');
            foreach (DB::getQueryLog() as $query) {
                if (preg_match('/^\s*(insert|update|delete|replace)\b/i', $query['query'])) {
                    $this->assertMatchesRegularExpression('/^update "reservations" set "status" = \?, "updated_at" = \?/i', $query['query']);
                }
            }
            DB::disableQueryLog();
            DB::flushQueryLog();
            $after = $reservation->fresh()->getAttributes();
            $this->assertSame('cancelled', $after['status']);
            $this->assertNotSame($before['updated_at'], $after['updated_at']);
            unset($before['status'], $before['updated_at'], $after['status'], $after['updated_at']);
            $this->assertSame($before, $after);
            $this->assertSame($tableBefore, $table->fresh()->getAttributes());
            $this->assertDatabaseCount('reservations', 1);
            $this->get(route('admin.reservations.show', $reservation))->assertOk()->assertSee('Đã hủy')->assertDontSee('Hủy đặt bàn');
        }
    }

    public function test_all_linked_order_statuses_block_without_order_changes(): void
    {
        [, , , $reservation] = $this->fixtures();
        $this->orderFixture();
        $id = DB::table('orders')->insertGetId(['reservation_id' => $reservation->id, 'status' => 'pending']);
        foreach (['pending', 'completed', 'cancelled'] as $status) {
            DB::table('orders')->where('id', $id)->update(['status' => $status]);
            $before = DB::table('orders')->get()->toJson();
            $this->patchJson(route('admin.reservations.cancel', $reservation), ['expected_status' => 'pending'])
                ->assertUnprocessable()->assertJsonValidationErrors('cancellation');
            $this->assertSame('pending', $reservation->fresh()->status);
            $this->assertSame($before, DB::table('orders')->get()->toJson());
        }
        $this->from(route('admin.reservations.show', $reservation))->followingRedirects()
            ->patch(route('admin.reservations.cancel', $reservation), ['expected_status' => 'pending'])
            ->assertOk()->assertSee('Không thể hủy đặt bàn đã có đơn hàng liên kết.');
    }

    public function test_forbidden_states_double_cancel_stale_state_and_bad_requests_are_rejected(): void
    {
        [, , , $reservation] = $this->fixtures();
        $this->orderFixture();
        foreach (['arrived', 'completed', 'no_show', 'cancelled'] as $status) {
            $reservation->forceFill(['status' => $status])->save();
            $this->patchJson(route('admin.reservations.cancel', $reservation), ['expected_status' => 'pending'])->assertUnprocessable();
            $this->assertSame($status, $reservation->fresh()->status);
            $this->get(route('admin.reservations.show', $reservation))->assertOk()->assertDontSee('Hủy đặt bàn');
        }
        $reservation->forceFill(['status' => 'confirmed'])->save();
        $this->patchJson(route('admin.reservations.cancel', $reservation), ['expected_status' => 'pending'])->assertUnprocessable();
        foreach ([null, 'arrived', ['pending']] as $expected) {
            $this->patchJson(route('admin.reservations.cancel', $reservation), ['expected_status' => $expected])->assertUnprocessable()->assertJsonValidationErrors('expected_status');
        }
        $this->assertSame('confirmed', $reservation->fresh()->status);
        $this->patch('/admin/reservations/99999/cancel', ['expected_status' => 'pending'])->assertNotFound();
        $this->get(route('admin.reservations.cancel', $reservation))->assertMethodNotAllowed();
    }

    public function test_inactive_deleted_masters_and_unassigned_table_do_not_prevent_cancellation(): void
    {
        [, $area, $table, $reservation] = $this->fixtures();
        $this->orderFixture();
        $area->forceFill(['status' => 'inactive'])->save();
        $table->forceFill(['status' => 'maintenance'])->save();
        $area->delete();
        $table->delete();
        $this->patch(route('admin.reservations.cancel', $reservation), ['expected_status' => 'pending'])->assertSessionHasNoErrors();
        $reservation->refresh()->forceFill(['status' => 'pending', 'table_id' => null])->save();
        $this->patch(route('admin.reservations.cancel', $reservation), ['expected_status' => 'pending'])->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $reservation->fresh()->status);
    }

    public function test_cancel_releases_interval_and_approve_cancel_stale_actions_reject(): void
    {
        [, , , $reservation] = $this->fixtures();
        $this->orderFixture();
        $other = $reservation->replicate();
        $other->save();
        $this->patch(route('admin.reservations.cancel', $reservation), ['expected_status' => 'pending'])->assertSessionHasNoErrors();
        app(ReservationApprovalService::class)->approve($other);
        $this->assertSame('confirmed', $other->fresh()->status);
        try {
            app(ReservationApprovalService::class)->approve($reservation->fresh());
            $this->fail('Cannot approve cancelled request.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('approval', $exception->errors());
        }
        $this->patchJson(route('admin.reservations.cancel', $other), ['expected_status' => 'pending'])->assertUnprocessable();
        $this->assertSame('confirmed', $other->fresh()->status);
    }

    public function test_storage_failure_rolls_back_and_hides_database_error(): void
    {
        [, , , $reservation] = $this->fixtures();
        $this->orderFixture();
        Reservation::saving(fn () => throw new QueryException('sqlite', 'private SQL', [], new \RuntimeException('private error')));
        try {
            $this->followingRedirects()->patch(route('admin.reservations.cancel', $reservation), ['expected_status' => 'pending'])
                ->assertOk()->assertSee('Không thể hủy đặt bàn do lỗi lưu trữ.')->assertDontSee('private SQL')->assertDontSee('Đã hủy yêu cầu đặt bàn.');
            $this->assertSame('pending', $reservation->fresh()->status);
        } finally {
            Reservation::flushEventListeners();
        }
    }

    public function test_guest_staff_production_and_missing_csrf_cannot_cancel(): void
    {
        [$admin, , , $reservation] = $this->fixtures();
        $this->orderFixture();
        $url = route('admin.reservations.cancel', $reservation);
        $this->app['auth']->forgetGuards();
        $this->patch($url, ['expected_status' => 'pending'])->assertRedirect(route('login'));
        $admin->forceFill(['role' => 'staff'])->save();
        $this->actingAs($admin, 'web')->patch($url, ['expected_status' => 'pending'])->assertForbidden();
        $admin->forceFill(['role' => 'admin'])->save();
        $this->actingAs($admin, 'web');
        $this->app->instance('env', 'local');
        $this->withSession(['_token' => 'test-csrf'])->patch($url, ['expected_status' => 'pending'])->assertStatus(419);
        $this->app->instance('env', 'production');
        $this->patch($url, ['expected_status' => 'pending', '_token' => 'test-csrf'])->assertForbidden();
        $this->get(route('admin.reservations.show', $reservation))->assertOk()->assertDontSee('Hủy đặt bàn');
        $this->app->instance('env', 'testing');
        $this->assertSame('pending', $reservation->fresh()->status);
    }
}
