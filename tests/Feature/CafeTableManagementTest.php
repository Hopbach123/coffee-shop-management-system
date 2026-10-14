<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\CafeTable;
use App\Models\User;
use App\Services\CafeTableManagementService;
use DomainException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CafeTableManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        foreach (['0001_01_01_000000_create_users_table.php', '2026_08_14_000002_create_areas_table.php', '2026_08_14_000006_create_cafe_tables_table.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        $this->withoutVite();
    }

    private function admin(): User
    {
        return User::forceCreate(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'test-only-password', 'role' => 'admin', 'status' => 'active']);
    }

    private function payload(Area $area, array $overrides = []): array
    {
        return array_merge(['area_id' => $area->id, 'table_code' => 'T-01', 'table_name' => null, 'capacity' => 4], $overrides);
    }

    public function test_authorization_and_csrf(): void
    {
        $area = Area::create(['name' => 'Area']);
        foreach (['/admin/tables', '/admin/tables/create'] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
        $this->post('/admin/tables', $this->payload($area))->assertRedirect(route('login'));
        $staff = $this->admin();
        $staff->forceFill(['role' => 'staff'])->save();
        $this->actingAs($staff, 'web');
        foreach (['/admin/tables', '/admin/tables/create'] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->post('/admin/tables', $this->payload($area))->assertForbidden();
        $staff->forceFill(['role' => 'admin'])->save();
        $this->app->instance('env', 'local');
        $this->post('/admin/tables', $this->payload($area))->assertStatus(419);
        $this->assertDatabaseCount('cafe_tables', 0);
    }

    public function test_create_uses_defaults_whitelist_and_transaction_without_changing_existing_data(): void
    {
        $area = Area::create(['name' => 'Area'])->fresh();
        $areaBefore = $area->getAttributes();
        $existing = CafeTable::forceCreate(['area_id' => $area->id, 'table_code' => 'OLD', 'status' => 'occupied'])->fresh();
        $tableBefore = $existing->getAttributes();
        $this->actingAs($this->admin(), 'web');
        CafeTable::creating(function (): void {
            $this->assertGreaterThan(0, DB::transactionLevel());
        });
        DB::enableQueryLog();
        try {
            $this->post('/admin/tables', $this->payload($area, ['table_code' => '  T-01  ', 'status' => 'occupied', 'qr_code' => 'forged', 'id' => 9999, 'deleted_at' => '2000-01-01', 'created_at' => '2000-01-01']))
                ->assertRedirect(route('admin.tables.index'))->assertSessionHas('success', 'Đã thêm bàn.');
        } finally {
            CafeTable::flushEventListeners();
        }
        foreach (DB::getQueryLog() as $query) {
            $this->assertDoesNotMatchRegularExpression('/\b(orders|reservations)\b/i', $query['query']);
        }
        DB::disableQueryLog();
        DB::flushQueryLog();
        $table = CafeTable::where('table_code', 'T-01')->firstOrFail();
        $this->assertSame('available', $table->status);
        $this->assertNull($table->qr_code);
        $this->assertNull($table->table_name);
        $this->assertNull($table->deleted_at);
        $this->assertNotSame(9999, $table->id);
        $this->assertNotSame('2000-01-01', $table->created_at->format('Y-m-d'));
        $this->assertSame($areaBefore, $area->fresh()->getAttributes());
        $this->assertSame($tableBefore, $existing->fresh()->getAttributes());
    }

    public function test_combined_search_filters_pagination_and_eager_area_loading(): void
    {
        $area = Area::create(['name' => 'Main Area']);
        $other = Area::create(['name' => 'Other Area']);
        $this->actingAs($this->admin(), 'web')->get('/admin/tables')->assertOk()->assertSee('Chưa có bàn');
        for ($i = 1; $i <= 16; $i++) {
            CafeTable::create(['area_id' => $area->id, 'table_code' => 'MATCH-'.$i]);
        }
        CafeTable::forceCreate(['area_id' => $area->id, 'table_code' => 'MATCH-occupied', 'status' => 'occupied']);
        CafeTable::create(['area_id' => $other->id, 'table_code' => 'MATCH-other']);
        CafeTable::create(['area_id' => $area->id, 'table_code' => 'MATCH-deleted'])->delete();
        $query = 'search=MATCH&area_id='.$area->id.'&status=available';
        $this->get('/admin/tables?'.$query)->assertOk()->assertSee('Main Area')->assertSee('—')->assertSee('Bàn trống')
            ->assertViewHas('tables', function ($tables): bool {
                foreach ($tables as $table) {
                    $this->assertTrue($table->relationLoaded('area'));
                }

                return $tables->total() === 16 && $tables->count() === 15;
            });
        $this->get('/admin/tables?'.$query.'&page=2')->assertOk()->assertViewHas('tables', fn ($tables) => $tables->count() === 1
            && str_contains($tables->previousPageUrl(), 'search=MATCH') && str_contains($tables->previousPageUrl(), 'status=available')
            && str_contains($tables->previousPageUrl(), 'area_id='.$area->id));
        $this->get('/admin/tables?status=occupied')->assertOk()->assertSee('Đang có khách')->assertViewHas('tables', fn ($tables) => $tables->total() === 1);
        $this->get('/admin/tables?search=Missing')->assertOk()->assertSee('Không tìm thấy bàn');
    }

    public function test_invalid_input_and_duplicate_codes_including_trashed_tables(): void
    {
        $area = Area::create(['name' => 'Area']);
        $this->actingAs($this->admin(), 'web');
        foreach ([['table_code', ''], ['table_code', '  '], ['table_code', str_repeat('a', 21)], ['table_code', ['bad']], ['table_name', str_repeat('a', 101)], ['area_id', 9999], ['area_id', 'bad'], ['capacity', 0], ['capacity', -1], ['capacity', 1.5], ['capacity', 'bad'], ['capacity', 2147483648]] as [$field, $value]) {
            $this->post('/admin/tables', $this->payload($area, [$field => $value]))->assertSessionHasErrors($field);
        }
        $table = CafeTable::create(['area_id' => $area->id, 'table_code' => 'T-01']);
        foreach ([false, true] as $deleted) {
            if ($deleted) {
                $table->delete();
            }
            $this->post('/admin/tables', $this->payload($area))->assertSessionHasErrors('table_code');
        }
        foreach (['status=reserved', 'area_id=9999', 'search[]=bad', 'status[]=bad'] as $query) {
            $this->get('/admin/tables?'.$query)->assertStatus(302)->assertSessionHasErrors();
        }
        $this->from('/admin/tables/create')->post('/admin/tables', $this->payload($area, ['capacity' => 0]))->assertStatus(302);
        $this->get('/admin/tables/create')->assertOk()->assertSee('value="T-01"', false)->assertSee('capacity-error');
    }

    public function test_only_active_non_deleted_areas_are_selectable_and_server_validated(): void
    {
        $active = Area::create(['name' => 'Active Area']);
        $inactive = Area::forceCreate(['name' => 'Inactive Area', 'status' => 'inactive']);
        $deleted = Area::create(['name' => 'Deleted Area']);
        $deleted->delete();
        $this->actingAs($this->admin(), 'web')->get('/admin/tables/create')->assertOk()->assertSee('Active Area')
            ->assertDontSee('Inactive Area')->assertDontSee('Deleted Area');
        foreach ([$inactive, $deleted] as $area) {
            $this->post('/admin/tables', $this->payload($area))->assertSessionHasErrors('area_id');
        }
        $active->forceFill(['status' => 'inactive'])->save();
        $this->get('/admin/tables/create')->assertOk()->assertSee('Chưa có khu vực đang hoạt động.');
    }

    public function test_service_rechecks_parent_current_state_and_rolls_back(): void
    {
        $area = Area::create(['name' => 'Area']);
        $data = $this->payload($area);
        $area->delete();
        $this->expectException(DomainException::class);
        try {
            app(CafeTableManagementService::class)->create($data);
        } finally {
            $this->assertDatabaseCount('cafe_tables', 0);
        }
    }
}
