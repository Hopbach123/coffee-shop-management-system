<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\CafeTable;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CafeTableIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        foreach (['0001_01_01_000000_create_users_table.php', '2026_08_14_000002_create_areas_table.php', '2026_08_14_000006_create_cafe_tables_table.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        foreach (['orders', 'reservations'] as $name) {
            Schema::create($name, function (Blueprint $table): void {
                $table->id();
                $table->foreignId('table_id')->nullable()->constrained('cafe_tables')->restrictOnDelete()->noActionOnUpdate();
                $table->string('status');
                $table->text('note')->nullable();
                $table->integer('guest_count')->default(2);
                $table->dateTime('reservation_end_at')->nullable();
                $table->timestamps();
            });
        }
        $this->withoutVite();
    }

    private function fixtures(): array
    {
        $admin = User::forceCreate(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'test-only-password', 'role' => 'admin', 'status' => 'active']);
        $area = Area::create(['name' => 'Area'])->fresh();
        $table = CafeTable::create(['area_id' => $area->id, 'table_code' => 'T1', 'capacity' => 4, 'qr_code' => 'keep'])->fresh();
        $this->actingAs($admin, 'web');

        return [$area, $table];
    }

    public function test_all_allowed_table_writes_preserve_area_other_tables_and_complete_history(): void
    {
        [$area, $table] = $this->fixtures();
        $other = CafeTable::create(['area_id' => $area->id, 'table_code' => 'OTHER'])->fresh();
        DB::table('orders')->insert(['table_id' => $table->id, 'status' => 'completed', 'note' => 'Historical order']);
        DB::table('reservations')->insert(['table_id' => $table->id, 'status' => 'completed', 'note' => 'Historical booking', 'reservation_end_at' => now()->subDay()]);
        $beforeArea = $area->getAttributes();
        $beforeOther = $other->getAttributes();
        $beforeOrders = DB::table('orders')->get()->all();
        $beforeReservations = DB::table('reservations')->get()->all();
        $this->put('/admin/tables/'.$table->id, ['area_id' => $area->id, 'table_code' => 'RENAMED', 'table_name' => 'Name', 'capacity' => 6])
            ->assertRedirect(route('admin.tables.index'));
        $this->patch('/admin/tables/'.$table->id.'/status', ['expected_status' => 'available', 'status' => 'maintenance'])
            ->assertRedirect(route('admin.tables.index'));
        $this->delete('/admin/tables/'.$table->id)->assertRedirect(route('admin.tables.index'));
        $this->assertSoftDeleted('cafe_tables', ['id' => $table->id]);
        $this->assertSame($beforeArea, $area->fresh()->getAttributes());
        $this->assertSame($beforeOther, $other->fresh()->getAttributes());
        $this->assertEquals($beforeOrders, DB::table('orders')->get()->all());
        $this->assertEquals($beforeReservations, DB::table('reservations')->get()->all());
        $this->assertTrue($table->orders()->first()->cafeTable->trashed());
        $this->assertTrue($table->reservations()->first()->cafeTable->trashed());
        $this->assertSame([$other->id], $area->cafeTables()->pluck('id')->all());
    }

    public function test_create_update_status_and_delete_database_failures_never_report_success(): void
    {
        Exceptions::fake();
        [$area, $table] = $this->fixtures();
        $before = $table->getAttributes();
        $payload = ['area_id' => $area->id, 'table_code' => 'NEW', 'table_name' => null, 'capacity' => 6];
        CafeTable::saving(fn () => throw new QueryException('sqlite', 'fixture SQL', [], new \RuntimeException('fixture failure')));
        try {
            $this->from('/admin/tables/create')->post('/admin/tables', $payload)->assertRedirect('/admin/tables/create')
                ->assertSessionHasErrors('table')->assertSessionMissing('success');
            $this->from('/admin/tables/'.$table->id.'/edit')->put('/admin/tables/'.$table->id, $payload)
                ->assertRedirect('/admin/tables/'.$table->id.'/edit')->assertSessionHasErrors('table')->assertSessionMissing('success');
            $this->patch('/admin/tables/'.$table->id.'/status', ['expected_status' => 'available', 'status' => 'maintenance'])
                ->assertRedirect(route('admin.tables.index'))->assertSessionHasErrors('status')->assertSessionMissing('success');
            $this->assertSame($before, $table->fresh()->getAttributes());
            $this->assertDatabaseCount('cafe_tables', 1);
        } finally {
            CafeTable::flushEventListeners();
        }
        CafeTable::deleting(fn () => throw new QueryException('sqlite', 'fixture SQL', [], new \RuntimeException('fixture failure')));
        try {
            $this->delete('/admin/tables/'.$table->id)->assertRedirect(route('admin.tables.index'));
            $this->get('/admin/tables')->assertOk()->assertSee('Không thể xóa bàn do ràng buộc dữ liệu hoặc lỗi lưu trữ.')
                ->assertDontSee('fixture failure')->assertDontSee('fixture SQL');
            $this->assertSame($before, $table->fresh()->getAttributes());
            Exceptions::assertReported(QueryException::class);
        } finally {
            CafeTable::flushEventListeners();
        }
    }

    public function test_route_matrix_and_rendered_action_targets_match_approved_status_policy(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())->filter(fn ($route) => str_starts_with($route->uri(), 'admin/tables'));
        $this->assertCount(7, $routes);
        $this->assertCount(7, $routes->map(fn ($route) => $route->getName())->unique());
        $this->assertCount(7, $routes->map(fn ($route) => implode('|', $route->methods()).' '.$route->uri())->unique());
        foreach ($routes as $route) {
            $this->assertContains('auth:web', $route->gatherMiddleware());
            $this->assertContains('role:admin', $route->gatherMiddleware());
        }
        [$area, $table] = $this->fixtures();
        $response = $this->get('/admin/tables');
        $response->assertOk()->assertSee(route('admin.tables.create'))->assertSee(route('admin.tables.edit', $table))
            ->assertSee(route('admin.tables.destroy', $table))->assertSee(route('admin.tables.status', $table))
            ->assertSee('Bạn có chắc muốn xóa mềm bàn này?')->assertSee('name="expected_status"', false)
            ->assertViewHas('transitions', fn ($transitions) => $transitions === ['available' => ['maintenance'], 'maintenance' => ['available'], 'occupied' => ['available']]);
    }
}
