<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\CafeTable;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CafeTableUpdateTest extends TestCase
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
            $table->foreignId('table_id')->nullable()->constrained('cafe_tables')->restrictOnDelete()->noActionOnUpdate();
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('reservations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('table_id')->nullable()->constrained('cafe_tables')->restrictOnDelete()->noActionOnUpdate();
            $table->string('status');
            $table->integer('guest_count');
            $table->dateTime('reservation_end_at');
            $table->timestamps();
        });
        $this->withoutVite();
    }

    private function fixtures(): array
    {
        $admin = User::forceCreate(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'test-only-password', 'role' => 'admin', 'status' => 'active']);
        $area = Area::create(['name' => 'Original Area']);
        $table = CafeTable::create(['area_id' => $area->id, 'table_code' => 'T1', 'capacity' => 8, 'qr_code' => 'existing-qr']);
        $this->actingAs($admin, 'web');

        return [$admin, $area, $table];
    }

    private function payload(CafeTable $table, array $overrides = []): array
    {
        return array_merge($table->only(['area_id', 'table_code', 'table_name', 'capacity']), $overrides);
    }

    public function test_edit_update_unique_validation_and_whitelist(): void
    {
        [$admin, $area, $table] = $this->fixtures();
        $this->get('/admin/tables/'.$table->id.'/edit')->assertOk()->assertSee('value="T1"', false)->assertSee('value="8"', false);
        $this->put('/admin/tables/'.$table->id, $this->payload($table, ['table_name' => 'Updated', 'status' => 'occupied', 'qr_code' => 'forged', 'id' => 9999, 'deleted_at' => '2000-01-01']))
            ->assertRedirect(route('admin.tables.index'))->assertSessionHas('success');
        $table->refresh();
        $this->assertSame('Updated', $table->table_name);
        $this->assertSame('available', $table->status);
        $this->assertSame('existing-qr', $table->qr_code);
        $this->assertNull($table->deleted_at);
        CafeTable::create(['area_id' => $area->id, 'table_code' => 'DUP']);
        foreach ([['table_code', 'DUP'], ['table_code', '  '], ['table_code', str_repeat('a', 21)], ['capacity', 0], ['capacity', -1], ['capacity', 1.5], ['capacity', 2147483648], ['area_id', 9999]] as [$field, $value]) {
            $this->put('/admin/tables/'.$table->id, $this->payload($table, [$field => $value]))->assertSessionHasErrors($field);
        }
        $this->from('/admin/tables/'.$table->id.'/edit')->put('/admin/tables/'.$table->id, $this->payload($table, ['table_code' => 'RETAIN', 'capacity' => 0]))->assertStatus(302);
        $this->get('/admin/tables/'.$table->id.'/edit')->assertOk()->assertSee('value="RETAIN"', false)->assertSee('capacity-error');
    }

    public function test_move_and_capacity_guards_preserve_reservations_orders_and_allow_safe_changes(): void
    {
        [$admin, $area, $table] = $this->fixtures();
        $destination = Area::create(['name' => 'Destination']);
        DB::table('reservations')->insert(['table_id' => $table->id, 'status' => 'confirmed', 'guest_count' => 6, 'reservation_end_at' => now()->addDay()]);
        $before = DB::table('reservations')->get()->all();
        $this->put('/admin/tables/'.$table->id, $this->payload($table, ['capacity' => 5]))->assertSessionHasErrors('table');
        $this->put('/admin/tables/'.$table->id, $this->payload($table, ['area_id' => $destination->id]))->assertSessionHasErrors('table');
        $this->assertSame(8, $table->fresh()->capacity);
        $this->assertEquals($before, DB::table('reservations')->get()->all());
        $this->put('/admin/tables/'.$table->id, $this->payload($table, ['capacity' => 6]))->assertSessionHasNoErrors();
        DB::table('reservations')->update(['status' => 'completed']);
        DB::table('orders')->insert(['table_id' => $table->id, 'status' => 'pending']);
        $this->put('/admin/tables/'.$table->id, $this->payload($table->fresh(), ['area_id' => $destination->id]))->assertSessionHasErrors('table');
        DB::table('orders')->update(['status' => 'completed']);
        $table->forceFill(['status' => 'occupied'])->save();
        $this->put('/admin/tables/'.$table->id, $this->payload($table, ['area_id' => $destination->id]))->assertSessionHasErrors('table');
        $table->forceFill(['status' => 'available'])->save();
        $history = DB::table('reservations')->get()->all();
        $orders = DB::table('orders')->get()->all();
        $this->put('/admin/tables/'.$table->id, $this->payload($table, ['area_id' => $destination->id, 'capacity' => 4]))
            ->assertRedirect(route('admin.tables.index'))->assertSessionHasNoErrors();
        $this->assertEquals($history, DB::table('reservations')->get()->all());
        $this->assertEquals($orders, DB::table('orders')->get()->all());
    }

    public function test_current_inactive_area_can_be_kept_but_new_inactive_or_deleted_area_cannot(): void
    {
        [$admin, $area, $table] = $this->fixtures();
        $area->forceFill(['status' => 'inactive'])->save();
        $this->get('/admin/tables/'.$table->id.'/edit')->assertOk()->assertSee('Original Area');
        $this->put('/admin/tables/'.$table->id, $this->payload($table))->assertSessionHasNoErrors();
        $other = Area::forceCreate(['name' => 'Inactive', 'status' => 'inactive']);
        $this->put('/admin/tables/'.$table->id, $this->payload($table, ['area_id' => $other->id]))->assertSessionHasErrors('area_id');
        $other->delete();
        $this->put('/admin/tables/'.$table->id, $this->payload($table, ['area_id' => $other->id]))->assertSessionHasErrors('area_id');
    }

    public function test_delete_blocks_live_use_and_preserves_completed_history_after_soft_delete(): void
    {
        [$admin, $area, $table] = $this->fixtures();
        foreach (['occupied', 'available'] as $status) {
            $table->forceFill(['status' => $status])->save();
            if ($status === 'available') {
                DB::table('orders')->insert(['table_id' => $table->id, 'status' => 'pending']);
            }
            $this->delete('/admin/tables/'.$table->id)->assertSessionHasErrors('table');
            $this->assertNull($table->fresh()->deleted_at);
        }
        DB::table('orders')->update(['status' => 'completed']);
        foreach (['pending', 'confirmed', 'arrived'] as $status) {
            DB::table('reservations')->insert(['table_id' => $table->id, 'status' => $status, 'guest_count' => 2, 'reservation_end_at' => now()->addDay()]);
            $this->delete('/admin/tables/'.$table->id)->assertSessionHasErrors('table');
            DB::table('reservations')->where('status', $status)->update(['reservation_end_at' => now()->subDay()]);
        }
        $orders = DB::table('orders')->get()->all();
        $reservations = DB::table('reservations')->get()->all();
        $this->delete('/admin/tables/'.$table->id)->assertRedirect(route('admin.tables.index'))->assertSessionHas('success');
        $this->assertSoftDeleted('cafe_tables', ['id' => $table->id]);
        $this->assertEquals($orders, DB::table('orders')->get()->all());
        $this->assertEquals($reservations, DB::table('reservations')->get()->all());
        $this->assertTrue($table->orders()->first()->cafeTable->trashed());
        $this->assertTrue($table->reservations()->first()->cafeTable->trashed());
        $this->post('/admin/tables', $this->payload($table))->assertSessionHasErrors('table_code');
    }

    public function test_guest_staff_csrf_missing_and_deleted_targets(): void
    {
        [$admin, $area, $table] = $this->fixtures();
        $this->post('/logout');
        $this->get('/admin/tables/'.$table->id.'/edit')->assertRedirect(route('login'));
        $this->put('/admin/tables/'.$table->id, $this->payload($table))->assertRedirect(route('login'));
        $this->delete('/admin/tables/'.$table->id)->assertRedirect(route('login'));
        $admin->forceFill(['role' => 'staff'])->save();
        $this->actingAs($admin, 'web')->get('/admin/tables/'.$table->id.'/edit')->assertForbidden();
        $this->put('/admin/tables/'.$table->id, $this->payload($table))->assertForbidden();
        $this->delete('/admin/tables/'.$table->id)->assertForbidden();
        $admin->forceFill(['role' => 'admin'])->save();
        $this->get('/admin/tables/9999/edit')->assertNotFound();
        $this->put('/admin/tables/9999', $this->payload($table))->assertNotFound();
        $this->delete('/admin/tables/9999')->assertNotFound();
        $this->get('/admin/tables/'.$table->id)->assertStatus(405);
        $this->app->instance('env', 'local');
        $this->put('/admin/tables/'.$table->id, $this->payload($table))->assertStatus(419);
        $this->delete('/admin/tables/'.$table->id)->assertStatus(419);
    }
}
