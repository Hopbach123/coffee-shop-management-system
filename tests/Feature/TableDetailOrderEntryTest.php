<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\CafeTable;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\CreatesTableStatusSources;
use Tests\TestCase;

class TableDetailOrderEntryTest extends TestCase
{
    use CreatesTableStatusSources;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        foreach (['0001_01_01_000000_create_users_table.php', '2026_08_14_000002_create_areas_table.php', '2026_08_14_000006_create_cafe_tables_table.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        $this->prepareStatusReadTables();
        $this->withoutVite();
    }

    private function admin(): User
    {
        return User::forceCreate(['name' => 'Admin', 'email' => 'detail@example.test', 'password' => 'test-only-password', 'role' => 'admin', 'status' => 'active']);
    }

    private function table(string $code = 'B1'): CafeTable
    {
        $area = Area::create(['name' => 'Area '.$code]);

        return CafeTable::forceCreate(['area_id' => $area->id, 'table_code' => $code, 'table_name' => 'Window', 'capacity' => 6, 'status' => 'available'])->fresh();
    }

    public function test_floor_link_opens_correct_read_only_table_detail_and_safe_back_link(): void
    {
        $table = $this->table();
        $other = $this->table('OTHER');
        $this->actingAs($this->admin(), 'web');
        $this->get('/admin/tables?view=floor')->assertOk()->assertSee(route('admin.tables.show', $table->id));
        $this->get(route('admin.tables.show', $table->id))->assertOk()->assertViewIs('admin.tables.detail')
            ->assertViewHas('table', fn ($value) => $value->id === $table->id && $value->area->id === $table->area_id)
            ->assertSee('B1')->assertSee('Area B1')->assertSee('6 khách')->assertSee('Trống')
            ->assertSee(route('admin.tables.index', ['view' => 'floor', 'area_id' => $table->area_id]))
            ->assertDontSee('OTHER')->assertSee('Làm mới');
        $this->get(route('admin.tables.show', $other->id))->assertOk()->assertViewHas('table', fn ($value) => $value->id === $other->id);
    }

    public function test_read_signals_without_writes_or_unauthorized_order_actions(): void
    {
        $table = $this->table();
        $before = $table->getAttributes();
        $this->actingAs($this->admin(), 'web');
        DB::enableQueryLog();
        $this->get(route('admin.tables.show', $table->id).'?order_id=999&return_url=https://example.test')
            ->assertOk()->assertSee('Chưa có đơn tại chỗ đang xử lý.')
            ->assertDontSee('href="https://example.test', false)->assertDontSee('/staff/orders')
            ->assertDontSee('name="table_id"', false)->assertDontSee('Không có đơn hàng');
        $tableQueries = 0;
        foreach (DB::getQueryLog() as $query) {
            $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete)\b/i', $query['query']);
            $this->assertDoesNotMatchRegularExpression('/\border_items\b/i', $query['query']);
            if (preg_match('/\b(areas|cafe_tables)\b/', $query['query'])) {
                $tableQueries++;
            }
        }
        DB::disableQueryLog();
        $this->assertSame(2, $tableQueries);
        $this->assertSame($before, $table->fresh()->getAttributes());
    }

    public function test_missing_malformed_deleted_table_and_deleted_area_are_not_found(): void
    {
        $this->actingAs($this->admin(), 'web');
        foreach (['9999', 'bad', '-1'] as $id) {
            $this->get('/admin/tables/'.$id.'/detail')->assertNotFound();
        }
        $deleted = $this->table();
        $deleted->delete();
        $this->get(route('admin.tables.show', $deleted->id))->assertNotFound();
        $orphaned = $this->table('OLD');
        $orphaned->area->delete();
        $this->get(route('admin.tables.show', $orphaned->id))->assertNotFound();
    }

    public function test_authorization_and_read_only_http_methods(): void
    {
        $table = $this->table();
        $url = route('admin.tables.show', $table->id);
        $this->get($url)->assertRedirect(route('login'));
        $actor = $this->admin();
        $actor->forceFill(['role' => 'staff'])->save();
        $this->actingAs($actor, 'web')->get($url)->assertForbidden();
        $actor->forceFill(['role' => 'admin'])->save();
        foreach (['post', 'put', 'patch', 'delete'] as $method) {
            $this->{$method}($url)->assertStatus(405);
        }
        $route = Route::getRoutes()->getByName('admin.tables.show');
        $this->assertSame(['GET', 'HEAD'], $route->methods());
        $this->assertContains('auth:web', $route->gatherMiddleware());
        $this->assertContains('role:admin', $route->gatherMiddleware());
    }

    public function test_refresh_reads_latest_status_and_escapes_text(): void
    {
        $table = $this->table('<b>B1</b>');
        $this->actingAs($this->admin(), 'web');
        $url = route('admin.tables.show', $table->id);
        $this->get($url)->assertOk()->assertSee('&lt;b&gt;B1&lt;/b&gt;', false)->assertDontSee('<b>B1</b>', false);
        $table->forceFill(['status' => 'maintenance'])->save();
        $this->get($url)->assertOk()->assertSee('Bảo trì')->assertSee('Bàn đang bảo trì.');
        $table->area->forceFill(['status' => 'inactive'])->save();
        $this->get($url)->assertOk()->assertSee('Khu vực ngừng hoạt động');
    }

    public function test_database_failure_displays_safe_unavailable_state(): void
    {
        $table = $this->table();
        $this->actingAs($this->admin(), 'web');
        Log::shouldReceive('warning')->once()->with('Table detail query failed.', ['sql_state' => null]);
        DB::connection()->beforeExecuting(function ($query): void {
            if (str_contains($query, 'cafe_tables')) {
                throw new QueryException('sqlite', 'private SQL', ['private bindings'], new \RuntimeException('private failure'));
            }
        });
        $this->get(route('admin.tables.show', $table->id))->assertOk()->assertSee('Chưa thể tải thông tin bàn')
            ->assertDontSee('private SQL')->assertDontSee('private failure')->assertViewHas('table', null);
    }
}
