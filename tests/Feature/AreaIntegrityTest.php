<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\CafeTable;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AreaIntegrityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        foreach (['0001_01_01_000000_create_users_table.php', '2026_08_14_000002_create_areas_table.php', '2026_08_14_000006_create_cafe_tables_table.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        // Minimal linked-history fixtures; not a substitute for complete MySQL migration tests.
        foreach (['orders', 'reservations'] as $name) {
            Schema::create($name, function (Blueprint $table): void {
                $table->id();
                $table->foreignId('table_id')->nullable()->constrained('cafe_tables')->restrictOnDelete()->noActionOnUpdate();
                $table->string('status');
                $table->text('note')->nullable();
                $table->timestamps();
            });
        }
        $this->withoutVite();
    }

    private function fixtures(): array
    {
        $admin = User::forceCreate(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'test-only-password', 'role' => 'admin', 'status' => 'active']);
        $area = Area::create(['name' => 'Original', 'description' => 'Original description']);
        $table = CafeTable::forceCreate(['area_id' => $area->id, 'table_code' => 'T1', 'status' => 'occupied'])->fresh();
        DB::table('orders')->insert(['table_id' => $table->id, 'status' => 'pending', 'note' => 'Preserve order']);
        DB::table('reservations')->insert(['table_id' => $table->id, 'status' => 'confirmed', 'note' => 'Preserve reservation']);

        return [$admin, $area, $table];
    }

    public function test_area_operations_preserve_table_and_linked_history_and_reject_deletion(): void
    {
        [$admin, $area, $table] = $this->fixtures();
        $beforeTable = $table->getAttributes();
        $beforeOrders = DB::table('orders')->get()->all();
        $beforeReservations = DB::table('reservations')->get()->all();
        $this->actingAs($admin, 'web')->put('/admin/areas/'.$area->id, ['name' => 'Updated', 'description' => 'Updated description'])
            ->assertRedirect(route('admin.areas.index'))->assertSessionHas('success', 'Đã cập nhật khu vực.');
        foreach (['inactive', 'active'] as $status) {
            $this->patch('/admin/areas/'.$area->id.'/status', ['status' => $status])
                ->assertRedirect(route('admin.areas.index'))->assertSessionHas('success', $status === 'active' ? 'Đã kích hoạt khu vực.' : 'Đã vô hiệu hóa khu vực.');
            $this->assertSame($beforeTable, $table->fresh()->getAttributes());
            $this->assertEquals($beforeOrders, DB::table('orders')->get()->all());
            $this->assertEquals($beforeReservations, DB::table('reservations')->get()->all());
        }
        $this->delete('/admin/areas/'.$area->id)->assertRedirect(route('admin.areas.index'));
        $this->get('/admin/areas')->assertOk()->assertSee('Không thể xóa khu vực còn bàn liên kết, kể cả bàn đã xóa mềm.');
        $this->assertNull($area->fresh()->deleted_at);
        $this->assertSame($beforeTable, $table->fresh()->getAttributes());
        $this->assertEquals($beforeOrders, DB::table('orders')->get()->all());
        $this->assertEquals($beforeReservations, DB::table('reservations')->get()->all());
        $this->assertSame([$table->id], $area->cafeTables()->pluck('id')->all());
        $this->assertSame($area->id, $table->fresh()->area->id);
        $this->assertSame(1, $table->orders()->count());
        $this->assertSame(1, $table->reservations()->count());
    }

    public function test_creating_and_deleting_empty_area_preserves_other_area_tables_and_history(): void
    {
        [$admin, $area, $table] = $this->fixtures();
        $beforeArea = $area->fresh()->getAttributes();
        $beforeTable = $table->getAttributes();
        $beforeOrders = DB::table('orders')->get()->all();
        $beforeReservations = DB::table('reservations')->get()->all();
        $this->actingAs($admin, 'web')->post('/admin/areas', ['name' => 'Empty Area'])
            ->assertRedirect(route('admin.areas.index'));
        $empty = Area::where('name', 'Empty Area')->firstOrFail();
        $this->assertFalse($empty->cafeTables()->exists());
        $this->delete('/admin/areas/'.$empty->id)->assertRedirect(route('admin.areas.index'))->assertSessionHas('success', 'Đã xóa mềm khu vực.');
        $this->assertSoftDeleted('areas', ['id' => $empty->id]);
        $this->assertSame($beforeArea, $area->fresh()->getAttributes());
        $this->assertSame($beforeTable, $table->fresh()->getAttributes());
        $this->assertEquals($beforeOrders, DB::table('orders')->get()->all());
        $this->assertEquals($beforeReservations, DB::table('reservations')->get()->all());
    }

    public function test_area_route_matrix_and_listing_actions_match_approved_policy(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())->filter(fn ($route) => str_starts_with($route->uri(), 'admin/areas'));
        $this->assertCount(7, $routes);
        $this->assertCount(7, $routes->map(fn ($route) => $route->getName())->unique());
        $this->assertCount(7, $routes->map(fn ($route) => implode('|', $route->methods()).' '.$route->uri())->unique());
        foreach ($routes as $route) {
            $this->assertContains('auth:web', $route->gatherMiddleware());
            $this->assertContains('role:admin', $route->gatherMiddleware());
        }
        [$admin, $area] = $this->fixtures();
        $this->actingAs($admin, 'web')->get('/admin/areas')->assertOk()
            ->assertSee(route('admin.areas.create'))->assertSee(route('admin.areas.edit', $area))
            ->assertSee(route('admin.areas.status', $area))->assertSee(route('admin.areas.destroy', $area))
            ->assertSee('value="PATCH"', false)->assertSee('value="DELETE"', false)
            ->assertSee('Bạn có chắc muốn xóa mềm khu vực này?');
    }
}
