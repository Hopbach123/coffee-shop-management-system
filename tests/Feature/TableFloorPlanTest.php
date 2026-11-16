<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\CafeTable;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\Concerns\CreatesTableStatusSources;
use Tests\TestCase;

class TableFloorPlanTest extends TestCase
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
        return User::forceCreate(['name' => 'Admin', 'email' => 'floor@example.test', 'password' => 'test-only-password', 'role' => 'admin', 'status' => 'active']);
    }

    private function table(Area $area, string $code, string $status = 'available', ?string $name = null): CafeTable
    {
        $table = CafeTable::forceCreate(['area_id' => $area->id, 'table_code' => $code, 'table_name' => $name, 'capacity' => 6, 'status' => $status]);
        if ($status === 'occupied') {
            DB::table('orders')->insert(['table_id' => $table->id, 'order_type' => 'dine_in', 'status' => 'pending']);
        }

        return $table;
    }

    public function test_summary_cards_and_area_counts_exclude_soft_deletes_without_excluding_inactive_areas(): void
    {
        $this->actingAs($this->admin(), 'web');
        $area = Area::create(['name' => 'Garden']);
        $inactive = Area::forceCreate(['name' => 'Upstairs', 'status' => 'inactive']);
        $deleted = Area::create(['name' => 'Removed']);
        $this->table($area, 'B1');
        $this->table($area, 'B2', 'occupied');
        $this->table($inactive, 'B3', 'maintenance');
        $this->table($area, 'Deleted table')->delete();
        $this->table($deleted, 'Deleted area table');
        $deleted->delete();
        $this->get('/admin/tables?view=floor')->assertOk()->assertViewIs('admin.tables.floor-plan')
            ->assertViewHas('summary', fn ($summary) => (int) $summary->total === 3 && (int) $summary->available === 1 && (int) $summary->occupied === 1 && (int) $summary->unknown === 1 && (int) $summary->maintenance === 0)
            ->assertViewHas('areas', fn ($areas) => $areas->count() === 2 && $areas->firstWhere('id', $area->id)->cafe_tables_count === 2)
            ->assertSee('B1')->assertSee('B2')->assertSee('B3')->assertSee('6 khách')->assertSee('Ngừng hoạt động')
            ->assertDontSee('Deleted table')->assertDontSee('Deleted area table')->assertDontSee('Removed')
            ->assertSee('floor-table-card--available')->assertSee('floor-table-card--occupied')->assertSee('floor-table-card--unknown');
    }

    public function test_search_by_name_or_code_is_grouped_with_area_status_filters_and_navigation_retains_filters(): void
    {
        $this->actingAs($this->admin(), 'web');
        $area = Area::create(['name' => 'Main']);
        $other = Area::create(['name' => 'Other']);
        $table = $this->table($area, 'B1', 'occupied', 'Window seat');
        $this->table($other, 'B2', 'occupied', 'Window seat');
        $this->table($area, 'B3', 'available', 'Window seat');
        $url = '/admin/tables?view=floor&search=Window&area_id='.$area->id.'&status=occupied';
        $this->get($url)->assertOk()->assertViewHas('tables', fn ($tables) => $tables->pluck('id')->all() === [$table->id])
            ->assertViewHas('summary', fn ($summary) => (int) $summary->total === 1 && (int) $summary->occupied === 1)
            ->assertSee('aria-current="page"', false)->assertSee('Xóa lọc')
            ->assertSee(route('admin.tables.index', ['search' => 'Window', 'area_id' => $other->id, 'status' => 'occupied', 'view' => 'floor']));
        $this->get('/admin/tables?view=floor&search=B3')->assertOk()->assertViewHas('tables', fn ($tables) => $tables->pluck('table_code')->all() === ['B3']);
    }

    public function test_pagination_preserves_floor_mode_filters_and_existing_latest_first_order(): void
    {
        $this->actingAs($this->admin(), 'web');
        $area = Area::create(['name' => 'Area']);
        for ($i = 0; $i < 16; $i++) {
            $this->table($area, 'MATCH-'.$i)->forceFill(['created_at' => '2026-01-01 00:00:00'])->save();
        }
        $firstId = CafeTable::min('id');
        $lastId = CafeTable::max('id');
        $query = 'view=floor&search=MATCH&area_id='.$area->id.'&status=available';
        $this->get('/admin/tables?'.$query)->assertOk()->assertViewHas('tables', function ($tables) use ($lastId): bool {
            $this->assertSame($lastId, $tables->first()->id);
            $this->assertTrue($tables->every(fn ($table) => $table->relationLoaded('area')));
            foreach (['view=floor', 'search=MATCH', 'status=available', 'page=2'] as $part) {
                $this->assertStringContainsString($part, $tables->nextPageUrl());
            }

            return $tables->count() === 15;
        })->assertViewHas('summary', fn ($summary) => (int) $summary->total === 16);
        $this->get('/admin/tables?'.$query.'&page=2')->assertOk()->assertViewHas('tables', fn ($tables) => $tables->pluck('id')->all() === [$firstId]);
    }

    public function test_empty_states_and_escaped_markup_without_fake_detail_actions_or_writes(): void
    {
        $this->actingAs($this->admin(), 'web');
        $this->get('/admin/tables?view=floor')->assertOk()->assertSee('Chưa có khu vực');
        $area = Area::create(['name' => '<b>Garden</b>']);
        $this->get('/admin/tables?view=floor')->assertOk()->assertSee('Chưa có bàn');
        $this->get('/admin/tables?view=floor&area_id='.$area->id)->assertOk()->assertSee('Khu vực chưa có bàn');
        $table = $this->table($area, '<b>B1</b>', 'maintenance', '<script>unsafe</script>')->fresh();
        $before = $table->getAttributes();
        DB::enableQueryLog();
        $this->get('/admin/tables?view=floor')->assertOk()->assertSee('&lt;b&gt;B1&lt;/b&gt;', false)
            ->assertSee('&lt;b&gt;Garden&lt;/b&gt;', false)->assertSee('&lt;script&gt;unsafe&lt;/script&gt;', false)
            ->assertDontSee('<script>unsafe</script>', false)->assertSee(route('admin.tables.show', $table->id))
            ->assertDontSee('href="#"', false)->assertDontSee('Tạo Order')->assertDontSee('name="expected_status"', false);
        foreach (DB::getQueryLog() as $query) {
            $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete)\b/i', $query['query']);
        }
        DB::disableQueryLog();
        $this->assertSame($before, $table->fresh()->getAttributes());
        $this->get('/admin/tables?view=floor&search=Missing')->assertOk()->assertSee('Không tìm thấy bàn');
        $this->get('/admin/tables?view=floor&page=999')->assertOk()->assertSee('Không tìm thấy bàn');
    }

    public function test_backend_authorization_and_filter_validation(): void
    {
        $this->get('/admin/tables?view=floor')->assertRedirect(route('login'));
        $admin = $this->admin();
        $this->actingAs($admin, 'web');
        $deleted = Area::create(['name' => 'Deleted']);
        $deleted->delete();
        foreach (['status=invalid', 'status[]=occupied', 'area_id=9999', 'area_id='.$deleted->id, 'area_id[]=1', 'search[]=bad', 'search='.str_repeat('x', 101)] as $query) {
            $this->get('/admin/tables?view=floor&'.$query)->assertRedirect()->assertSessionHasErrors();
        }
        $admin->forceFill(['role' => 'staff'])->save();
        $this->get('/admin/tables?view=floor')->assertForbidden();
    }

    public function test_query_count_does_not_grow_per_table(): void
    {
        $this->actingAs($this->admin(), 'web');
        $area = Area::create(['name' => 'Area']);
        $this->table($area, 'B1');
        $measure = function (): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->get('/admin/tables?view=floor')->assertOk();
            $queries = collect(DB::getQueryLog())->filter(fn ($entry) => preg_match('/\b(areas|cafe_tables)\b/', $entry['query']));
            DB::disableQueryLog();

            return $queries->count();
        };
        $this->assertSame(5, $measure());
        for ($i = 2; $i <= 15; $i++) {
            $this->table($area, 'B'.$i);
        }
        $this->assertSame(5, $measure());
    }

    public function test_database_error_has_safe_unavailable_state_without_zero_metrics(): void
    {
        $this->actingAs($this->admin(), 'web');
        Log::shouldReceive('warning')->once()->with('Table floor plan query failed.', ['sql_state' => null]);
        DB::connection()->beforeExecuting(function ($query): void {
            if (str_contains($query, 'as total')) {
                throw new QueryException('sqlite', 'private SQL', ['private binding'], new \RuntimeException('private failure'));
            }
        });
        $this->get('/admin/tables?view=floor')->assertOk()->assertSee('Chưa thể tải sơ đồ bàn')
            ->assertDontSee('Tổng số bàn')->assertDontSee('private SQL')->assertDontSee('private failure')
            ->assertViewHas('summary', null)->assertViewHas('tables', null);
    }
}
