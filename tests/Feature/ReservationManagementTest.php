<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ReservationController;
use App\Models\Area;
use App\Models\CafeTable;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReservationManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        foreach (['0001_01_01_000000_create_users_table.php', '2026_08_14_000001_create_customers_table.php', '2026_08_14_000002_create_areas_table.php', '2026_08_14_000006_create_cafe_tables_table.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        // SQLite fixture for the existing schema; production CHECK ALTER syntax is MySQL-only.
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
            $table->enum('status', array_keys(ReservationController::STATUS_LABELS))->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
        $this->withoutVite();
    }

    private function admin(): User
    {
        return User::forceCreate(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'test-only-password', 'role' => 'admin', 'status' => 'active']);
    }

    private function reservation(array $attributes = []): Reservation
    {
        return Reservation::forceCreate(array_merge([
            'customer_name' => 'Snapshot Guest', 'customer_phone' => '0901234567', 'guest_count' => 2,
            'reservation_start_at' => '2026-10-19 12:00:00', 'reservation_end_at' => '2026-10-19 13:00:00',
            'status' => 'pending', 'created_at' => '2026-01-01 00:00:00',
        ], $attributes));
    }

    public function test_only_admin_can_list_and_view_details_and_unknown_id_is_404(): void
    {
        $reservation = $this->reservation();
        foreach (['/admin/reservations', '/admin/reservations/'.$reservation->id] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
        $admin = $this->admin();
        $admin->forceFill(['role' => 'staff'])->save();
        $this->actingAs($admin, 'web');
        foreach (['/admin/reservations', '/admin/reservations/'.$reservation->id] as $url) {
            $this->get($url)->assertForbidden();
        }
        $admin->forceFill(['role' => 'admin'])->save();
        $this->get('/admin/reservations')->assertOk();
        $this->get('/admin/reservations/'.$reservation->id)->assertOk();
        $this->get('/admin/reservations/99999')->assertNotFound();
    }

    public function test_empty_null_relations_snapshot_escaping_and_all_statuses(): void
    {
        $this->actingAs($this->admin(), 'web')->get('/admin/reservations')->assertOk()->assertSee('Chưa có đặt bàn');
        foreach (ReservationController::STATUS_LABELS as $status => $label) {
            $reservation = $this->reservation(['status' => $status, 'customer_name' => '<script>guest</script>', 'note' => '<b>note</b>']);
            $this->get('/admin/reservations?status='.$status)->assertOk()->assertSee($label)
                ->assertSee('Chưa gán bàn')->assertSee('&lt;script&gt;guest&lt;/script&gt;', false)->assertDontSee('<script>guest</script>', false);
            $this->get('/admin/reservations/'.$reservation->id)->assertOk()->assertSee($label)
                ->assertSee('Không có hồ sơ khách hàng liên kết')->assertSee('Không ghi nhận người tạo')
                ->assertSee('&lt;b&gt;note&lt;/b&gt;', false)->assertDontSee('<b>note</b>', false)->assertSee('19/10/2026 12:00')->assertSee('19/10/2026 13:00');
        }
        $this->get('/admin/reservations?search=missing')->assertOk()->assertSee('Không tìm thấy đặt bàn');
    }

    public function test_combined_filters_group_phone_or_and_keep_pagination_and_stable_order(): void
    {
        $this->actingAs($this->admin(), 'web');
        $area = Area::create(['name' => 'Historical floor']);
        $table = CafeTable::create(['area_id' => $area->id, 'table_code' => 'T1', 'capacity' => 4]);
        $ids = [];
        for ($i = 0; $i < 16; $i++) {
            $ids[] = $this->reservation(['table_id' => $table->id, 'customer_phone' => 'needle'])->id;
        }
        $this->reservation(['table_id' => $table->id, 'customer_phone' => 'needle', 'status' => 'cancelled']);
        $this->reservation(['table_id' => $table->id, 'customer_phone' => 'needle', 'reservation_start_at' => '2026-10-20 00:00:00', 'reservation_end_at' => '2026-10-20 01:00:00']);
        $this->reservation(['customer_phone' => 'needle']);
        $this->reservation(['table_id' => $table->id]);
        $table->delete();
        $area->delete();
        $query = http_build_query(['search' => ' needle ', 'status' => 'pending', 'date' => '2026-10-19', 'area_id' => $area->id]);
        $this->get('/admin/reservations?'.$query)->assertOk()->assertSee('Historical floor')
            ->assertViewHas('filters', fn ($filters) => $filters['search'] === 'needle')
            ->assertViewHas('reservations', function ($rows) use ($ids) {
                $this->assertSame(16, $rows->total());
                $this->assertSame($ids[15], $rows->first()->id);
                $url = $rows->nextPageUrl();
                foreach (['search=needle', 'status=pending', 'date=2026-10-19', 'area_id=', 'page=2'] as $part) {
                    $this->assertStringContainsString($part, $url);
                }

                return true;
            });
        $this->get('/admin/reservations?'.$query.'&page=2')->assertOk()
            ->assertViewHas('reservations', fn ($rows) => $rows->pluck('id')->all() === [$ids[0]]);
        $this->get('/admin/reservations?search=Snapshot')->assertOk()->assertViewHas('reservations', fn ($rows) => $rows->total() === 20);
    }

    public function test_day_filter_uses_start_not_creation_or_end_and_includes_midnight(): void
    {
        $this->actingAs($this->admin(), 'web');
        $first = $this->reservation(['reservation_start_at' => '2026-10-19 00:00:00']);
        $last = $this->reservation(['reservation_start_at' => '2026-10-19 23:59:59', 'reservation_end_at' => '2026-10-20 02:00:00']);
        $this->reservation(['reservation_start_at' => '2026-10-18 23:00:00', 'reservation_end_at' => '2026-10-19 01:00:00', 'created_at' => '2026-10-19 00:00:00']);
        $this->reservation(['reservation_start_at' => '2026-10-20 00:00:00', 'reservation_end_at' => '2026-10-20 01:00:00']);
        $this->get('/admin/reservations?date=2026-10-19')->assertOk()
            ->assertViewHas('reservations', fn ($rows) => $rows->pluck('id')->all() === [$last->id, $first->id]);
    }

    public function test_invalid_filters_are_rejected(): void
    {
        $this->actingAs($this->admin(), 'web');
        foreach ([['status' => 'reserved'], ['status' => ['pending']], ['search' => ['bad']], ['search' => str_repeat('x', 256)], ['date' => '2026-02-30'], ['date' => '19/10/2026'], ['area_id' => 999], ['area_id' => 'bad']] as $input) {
            $this->getJson('/admin/reservations?'.http_build_query($input))->assertUnprocessable()->assertJsonValidationErrors(array_key_first($input));
        }
    }

    public function test_historical_relations_are_eager_loaded_and_pages_do_not_write_business_data(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'web');
        $creator = User::forceCreate(['name' => 'Historical creator', 'email' => 'creator@example.test', 'password' => 'test-only-password', 'role' => 'staff', 'status' => 'active']);
        $customer = Customer::create(['name' => 'Current profile']);
        $area = Area::create(['name' => 'Old floor']);
        $table = CafeTable::create(['area_id' => $area->id, 'table_code' => 'OLD', 'capacity' => 4]);
        $reservation = $this->reservation(['customer_id' => $customer->id, 'table_id' => $table->id, 'created_by' => $creator->id]);
        foreach ([$creator, $customer, $area, $table] as $model) {
            $model->delete();
        }
        $before = DB::table('reservations')->get()->toJson();
        DB::enableQueryLog();
        $this->get('/admin/reservations')->assertOk()->assertSee('Snapshot Guest')->assertSee('Old floor')
            ->assertViewHas('reservations', function ($rows) {
                foreach ($rows as $row) {
                    foreach (['creator', 'customer', 'cafeTable'] as $relation) {
                        $this->assertTrue($row->relationLoaded($relation));
                    }
                    $this->assertTrue($row->cafeTable->relationLoaded('area'));
                }

                return true;
            });
        $this->get('/admin/reservations/'.$reservation->id)->assertOk()->assertSee('Historical creator')->assertSee('Current profile')->assertSee('OLD')
            ->assertViewHas('reservation', fn ($row) => array_keys($row->creator->getAttributes()) === ['id', 'name']);
        foreach (DB::getQueryLog() as $query) {
            $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete|replace|alter|drop)\b/i', $query['query']);
        }
        DB::disableQueryLog();
        DB::flushQueryLog();
        $this->assertSame($before, DB::table('reservations')->get()->toJson());
        $this->assertSame('available', $table->fresh()->status);
    }
}
