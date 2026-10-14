<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\CafeTable;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CafeTableStatusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        foreach (['0001_01_01_000000_create_users_table.php', '2026_08_14_000002_create_areas_table.php', '2026_08_14_000006_create_cafe_tables_table.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('table_id')->constrained('cafe_tables')->restrictOnDelete()->noActionOnUpdate();
            $table->string('status');
        });
        Schema::create('reservations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('table_id')->constrained('cafe_tables')->restrictOnDelete()->noActionOnUpdate();
            $table->string('status');
            $table->dateTime('reservation_end_at');
        });
        $this->withoutVite();
    }

    private function fixtures(): array
    {
        $admin = User::forceCreate(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'test-only-password', 'role' => 'admin', 'status' => 'active']);
        $area = Area::create(['name' => 'Area']);
        $table = CafeTable::create(['area_id' => $area->id, 'table_code' => 'T1', 'qr_code' => 'keep-qr'])->fresh();
        $this->actingAs($admin, 'web');

        return [$admin, $area, $table];
    }

    private function change(CafeTable $table, string $expected, string $target)
    {
        return $this->patch('/admin/tables/'.$table->id.'/status', ['expected_status' => $expected, 'status' => $target, 'area_id' => 9999, 'capacity' => 999, 'qr_code' => 'forged']);
    }

    public function test_approved_transitions_only_change_status_and_reject_stale_request(): void
    {
        [$admin, $area, $table] = $this->fixtures();
        $before = $table->getAttributes();
        $this->change($table, 'available', 'maintenance')->assertRedirect(route('admin.tables.index'))->assertSessionHas('success');
        $this->change($table, 'available', 'occupied')->assertSessionHasErrors('status')->assertSessionMissing('success');
        $this->assertSame('maintenance', $table->fresh()->status);
        $this->change($table, 'maintenance', 'available')->assertSessionHasNoErrors();
        $table->forceFill(['status' => 'occupied'])->save();
        $this->change($table, 'occupied', 'available')->assertSessionHasNoErrors();
        foreach (['id', 'area_id', 'table_code', 'table_name', 'capacity', 'qr_code', 'created_at', 'deleted_at'] as $field) {
            $this->assertSame($before[$field], $table->fresh()->getAttributes()[$field]);
        }
        $this->change($table, 'available', 'occupied')->assertSessionHasErrors('status');
        $table->refresh()->forceFill(['status' => 'occupied'])->save();
        $this->change($table, 'occupied', 'maintenance')->assertSessionHasErrors('status');
        $this->assertSame('occupied', $table->fresh()->status);
    }

    public function test_live_order_and_reservations_block_changes_without_history_mutation(): void
    {
        [$admin, $area, $table] = $this->fixtures();
        DB::table('orders')->insert(['table_id' => $table->id, 'status' => 'pending']);
        $orderBefore = DB::table('orders')->get()->all();
        $this->change($table, 'available', 'maintenance')->assertSessionHasErrors('status');
        $this->assertEquals($orderBefore, DB::table('orders')->get()->all());
        DB::table('orders')->update(['status' => 'completed']);
        foreach (['pending', 'confirmed', 'arrived'] as $status) {
            $id = DB::table('reservations')->insertGetId(['table_id' => $table->id, 'status' => $status, 'reservation_end_at' => now()->addDay()]);
            $before = DB::table('reservations')->get()->all();
            $this->change($table, 'available', 'maintenance')->assertSessionHasErrors('status');
            $this->assertEquals($before, DB::table('reservations')->get()->all());
            DB::table('reservations')->where('id', $id)->update(['reservation_end_at' => now()->subDay()]);
        }
        $before = DB::table('reservations')->get()->all();
        $this->change($table, 'available', 'maintenance')->assertSessionHasNoErrors();
        $this->assertEquals($before, DB::table('reservations')->get()->all());
        $this->assertSame('completed', DB::table('orders')->first()->status);
    }

    public function test_reopening_requires_active_non_deleted_area_and_ui_shows_approved_actions(): void
    {
        [$admin, $area, $table] = $this->fixtures();
        $this->get('/admin/tables')->assertOk()->assertSee('Bảo trì')->assertSee('name="expected_status"', false);
        $this->change($table, 'available', 'maintenance')->assertSessionHasNoErrors();
        $this->get('/admin/tables')->assertOk()->assertSee('Mở lại bàn')->assertDontSee('value="occupied" name="status"', false);
        $area->forceFill(['status' => 'inactive'])->save();
        $this->change($table, 'maintenance', 'available')->assertSessionHasErrors('status');
        $this->assertSame('maintenance', $table->fresh()->status);
        $area->delete();
        $this->change($table, 'maintenance', 'available')->assertSessionHasErrors('status');
    }

    public function test_validation_access_csrf_missing_and_deleted_targets(): void
    {
        [$admin, $area, $table] = $this->fixtures();
        foreach ([['status' => 'reserved', 'expected_status' => 'available'], ['status' => 'bad', 'expected_status' => 'available'], ['status' => 'maintenance'], ['status' => ['maintenance'], 'expected_status' => 'available']] as $data) {
            $this->patch('/admin/tables/'.$table->id.'/status', $data)->assertSessionHasErrors();
        }
        $this->patch('/admin/tables/9999/status', ['status' => 'maintenance', 'expected_status' => 'available'])->assertNotFound();
        $this->get('/admin/tables/'.$table->id.'/status')->assertStatus(405);
        $this->post('/logout');
        $this->change($table, 'available', 'maintenance')->assertRedirect(route('login'));
        $admin->forceFill(['role' => 'staff'])->save();
        $this->actingAs($admin, 'web');
        $this->change($table, 'available', 'maintenance')->assertForbidden();
        $admin->forceFill(['role' => 'admin'])->save();
        $this->app->instance('env', 'local');
        $this->change($table, 'available', 'maintenance')->assertStatus(419);
        $this->assertSame('available', $table->fresh()->status);
    }
}
