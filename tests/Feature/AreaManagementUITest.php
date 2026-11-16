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

class AreaManagementUITest extends TestCase
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

    private function loginAdmin(): void
    {
        $this->actingAs(User::forceCreate(['name' => 'Admin', 'email' => 'ui@example.test', 'password' => 'test-only-password', 'role' => 'admin', 'status' => 'active']), 'web');
    }

    private function table(Area $area, string $status): CafeTable
    {
        return CafeTable::forceCreate(['area_id' => $area->id, 'table_code' => 'T'.(CafeTable::withTrashed()->count() + 1), 'status' => $status]);
    }

    private function counts(object $summary): array
    {
        return array_map('intval', (array) $summary);
    }

    public function test_counts_exclude_deleted_records_and_follow_filters_across_all_pages(): void
    {
        $this->loginAdmin();
        $active = Area::create(['name' => 'Garden']);
        $inactive = Area::forceCreate(['name' => 'Garden upstairs', 'status' => 'inactive']);
        $deleted = Area::create(['name' => 'Removed']);
        foreach (['available', 'occupied', 'maintenance'] as $status) {
            $this->table($active, $status);
        }
        $this->table($active, 'available')->delete();
        $this->table($inactive, 'occupied');
        $this->table($deleted, 'available');
        $deleted->delete();
        for ($i = 0; $i < 16; $i++) {
            Area::create(['name' => 'Empty '.$i]);
        }
        $this->get('/admin/areas')->assertOk()->assertViewHas('summary', fn ($summary) => $this->counts($summary) === [
            'areas_count' => 18, 'tables_count' => 4, 'available_count' => 1, 'occupied_count' => 0, 'maintenance_count' => 1, 'reserved_count' => 0, 'conflict_count' => 1, 'unknown_count' => 1,
        ])->assertViewHas('areas', fn ($areas) => $areas->count() === 15 && $areas->total() === 18);
        $this->get('/admin/areas?search=Garden&status=active')->assertOk()
            ->assertViewHas('summary', fn ($summary) => $this->counts($summary) === [
                'areas_count' => 1, 'tables_count' => 3, 'available_count' => 1, 'occupied_count' => 0, 'maintenance_count' => 1, 'reserved_count' => 0, 'conflict_count' => 1, 'unknown_count' => 0,
            ])->assertViewHas('areas', fn ($areas) => $areas->first()->tables_count === 3 && $areas->first()->conflict_count === 1)
            ->assertSee(route('admin.tables.index', ['area_id' => $active->id, 'view' => 'floor']))->assertDontSee('Garden upstairs');
        $this->get('/admin/areas?status=inactive')->assertOk()->assertSee('Garden upstairs')
            ->assertViewHas('summary', fn ($summary) => (int) $summary->tables_count === 1);
    }

    public function test_card_navigation_escaping_empty_and_no_results(): void
    {
        $this->loginAdmin();
        $this->get('/admin/areas')->assertOk()->assertSee('Chưa có khu vực');
        $area = Area::create(['name' => '<script>test</script>', 'description' => '<b>unsafe</b>']);
        $before = $area->fresh()->getAttributes();
        $this->get('/admin/areas')->assertOk()->assertSee('Chưa có bàn trong khu vực.')
            ->assertSee('&lt;script&gt;test&lt;/script&gt;', false)->assertDontSee('<script>test</script>', false)
            ->assertSee('&lt;b&gt;unsafe&lt;/b&gt;', false)->assertSee(route('admin.areas.create'))
            ->assertSee(route('admin.areas.edit', $area))->assertSee(route('admin.areas.destroy', $area))
            ->assertSee(route('admin.areas.status', $area))->assertSee(route('admin.tables.index', ['area_id' => $area->id, 'view' => 'floor']))
            ->assertSee('value="DELETE"', false)->assertSee('value="PATCH"', false)->assertSee('name="_token"', false);
        $this->get('/admin/areas?search=Missing&status=inactive')->assertOk()->assertSee('Không tìm thấy khu vực')->assertSee('Xóa lọc');
        $this->get('/admin/areas?page=999')->assertOk()->assertSee('Không tìm thấy khu vực')->assertDontSee('Chưa có khu vực');
        $this->assertSame($before, $area->fresh()->getAttributes());
    }

    public function test_pagination_retains_both_filters_and_stable_sort(): void
    {
        $this->loginAdmin();
        $ids = [];
        for ($i = 0; $i < 16; $i++) {
            $ids[] = Area::forceCreate(['name' => 'Floor '.$i, 'status' => 'inactive', 'created_at' => '2026-01-01 00:00:00'])->id;
        }
        $this->get('/admin/areas?search=Floor&status=inactive')->assertOk()
            ->assertViewHas('areas', fn ($areas) => $areas->first()->id === $ids[15] && str_contains($areas->nextPageUrl(), 'status=inactive') && str_contains($areas->nextPageUrl(), 'search=Floor'));
        $this->get('/admin/areas?search=Floor&status=inactive&page=2')->assertOk()
            ->assertViewHas('areas', fn ($areas) => $areas->pluck('id')->all() === [$ids[0]])
            ->assertViewHas('summary', fn ($summary) => (int) $summary->areas_count === 16);
    }

    public function test_validation_and_authorization(): void
    {
        $this->get('/admin/areas')->assertRedirect(route('login'));
        $this->loginAdmin();
        foreach (['status=reserved', 'status[]=active'] as $query) {
            $this->get('/admin/areas?'.$query)->assertSessionHasErrors('status');
        }
        $this->get('/admin/areas?search[]=x')->assertSessionHasErrors('search');
        auth('web')->user()->forceFill(['role' => 'staff'])->save();
        $this->get('/admin/areas')->assertForbidden();
    }

    public function test_query_count_is_bounded_as_card_count_grows(): void
    {
        $this->loginAdmin();
        $measure = function (): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->get('/admin/areas')->assertOk();
            $queries = collect(DB::getQueryLog())->filter(fn ($entry) => preg_match('/\b(areas|cafe_tables)\b/', $entry['query']));
            DB::disableQueryLog();

            return $queries->count();
        };
        $this->table(Area::create(['name' => 'One']), 'occupied');
        $this->assertSame(3, $measure());
        for ($i = 0; $i < 14; $i++) {
            $this->table(Area::create(['name' => 'More '.$i]), 'available');
        }
        $this->assertSame(3, $measure());
    }

    public function test_database_error_has_unavailable_state_and_no_fake_zero_or_sensitive_log(): void
    {
        $this->loginAdmin();
        Log::shouldReceive('warning')->once()->with('Area overview query failed.', ['sql_state' => null]);
        DB::connection()->beforeExecuting(function ($query): void {
            if (str_contains($query, 'area_counts')) {
                throw new QueryException('sqlite', 'sensitive SQL', ['private search'], new \RuntimeException('private failure'));
            }
        });
        $this->get('/admin/areas')->assertOk()->assertSee('Chưa thể tải khu vực')
            ->assertDontSee('Tổng số bàn')->assertDontSee('Chưa có khu vực')->assertDontSee('sensitive SQL')
            ->assertDontSee('private failure')->assertViewHas('summary', null)->assertViewHas('areas', null);
    }
}
