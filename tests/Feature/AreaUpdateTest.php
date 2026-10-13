<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\CafeTable;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Tests\TestCase;

class AreaUpdateTest extends TestCase
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

    public function test_edit_update_and_validation_preserve_unapproved_fields(): void
    {
        $area = Area::create(['name' => 'Original', 'description' => 'Existing'])->fresh();
        $created = $area->getRawOriginal('created_at');
        $this->actingAs($this->admin(), 'web')->get('/admin/areas/'.$area->id.'/edit')->assertOk()
            ->assertSee('value="Original"', false)->assertSee('Existing')->assertSee('value="PUT"', false);
        $this->put('/admin/areas/'.$area->id, ['name' => '  Updated  ', 'description' => null, 'status' => 'inactive', 'id' => 9999, 'created_at' => '2000-01-01', 'deleted_at' => '2000-01-01'])
            ->assertRedirect(route('admin.areas.index'))->assertSessionHas('success');
        $area->refresh();
        $this->assertSame('Updated', $area->name);
        $this->assertSame('active', $area->status);
        $this->assertNull($area->description);
        $this->assertNull($area->deleted_at);
        $this->assertSame($created, $area->getRawOriginal('created_at'));
        Area::create(['name' => 'Duplicate']);
        $this->put('/admin/areas/'.$area->id, ['name' => 'Duplicate'])->assertSessionHasNoErrors();
        foreach (['', '   ', str_repeat('a', 101), ['bad']] as $name) {
            $this->put('/admin/areas/'.$area->id, ['name' => $name])->assertSessionHasErrors('name');
        }
        $this->put('/admin/areas/'.$area->id, ['name' => 'Valid', 'description' => ['bad']])->assertSessionHasErrors('description');
        $this->from('/admin/areas/'.$area->id.'/edit')->put('/admin/areas/'.$area->id, ['name' => '', 'description' => 'Retained'])->assertStatus(302);
        $this->get('/admin/areas/'.$area->id.'/edit')->assertOk()->assertSee('Retained')->assertSee('name-error');
    }

    public function test_status_preserves_occupied_table_and_only_mutates_area_status(): void
    {
        $area = Area::create(['name' => 'Area', 'description' => 'Keep']);
        $table = CafeTable::forceCreate(['area_id' => $area->id, 'table_code' => 'T1', 'status' => 'occupied'])->fresh();
        $before = $table->getAttributes();
        $this->actingAs($this->admin(), 'web');
        DB::enableQueryLog();
        foreach (['inactive', 'inactive', 'active'] as $status) {
            $this->patch('/admin/areas/'.$area->id.'/status', ['status' => $status, 'name' => 'Forged', 'description' => 'Forged'])
                ->assertRedirect(route('admin.areas.index'))->assertSessionHas('success');
            $this->assertSame($status, $area->fresh()->status);
            $this->assertSame($before, $table->fresh()->getAttributes());
        }
        foreach (DB::getQueryLog() as $query) {
            $this->assertDoesNotMatchRegularExpression('/\b(orders|reservations)\b/i', $query['query']);
        }
        DB::disableQueryLog();
        DB::flushQueryLog();
        $this->assertSame('Area', $area->fresh()->name);
        $this->assertSame('Keep', $area->fresh()->description);
        foreach (['bad', null, ['active']] as $status) {
            $this->patch('/admin/areas/'.$area->id.'/status', ['status' => $status])->assertSessionHasErrors('status');
        }
    }

    public function test_delete_rejects_active_and_soft_deleted_tables_without_changing_them(): void
    {
        $area = Area::create(['name' => 'Area']);
        $table = CafeTable::create(['area_id' => $area->id, 'table_code' => 'T1']);
        $this->actingAs($this->admin(), 'web');
        foreach ([false, true] as $deleted) {
            if ($deleted) {
                $table->delete();
            }
            $before = $table->fresh()->getAttributes();
            $this->delete('/admin/areas/'.$area->id)->assertRedirect(route('admin.areas.index'))->assertSessionHasErrors('area')->assertSessionMissing('success');
            $this->assertNull($area->fresh()->deleted_at);
            $this->assertSame($before, $table->fresh()->getAttributes());
        }
        $this->assertDatabaseCount('cafe_tables', 1);
    }

    public function test_empty_area_soft_delete_runs_in_transaction_and_disappears_from_listing(): void
    {
        $area = Area::create(['name' => 'Removed']);
        $this->actingAs($this->admin(), 'web');
        Area::deleting(function (): void {
            $this->assertGreaterThan(0, DB::transactionLevel());
        });
        try {
            $this->delete('/admin/areas/'.$area->id)->assertRedirect(route('admin.areas.index'))->assertSessionHas('success', 'Đã xóa mềm khu vực.');
        } finally {
            Area::flushEventListeners();
        }
        $this->assertSoftDeleted('areas', ['id' => $area->id]);
        $this->assertDatabaseCount('areas', 1);
        $this->get('/admin/areas')->assertOk()->assertDontSee('Removed');
        $this->delete('/admin/areas/'.$area->id)->assertNotFound();
    }

    public function test_access_missing_targets_csrf_and_methods(): void
    {
        $area = Area::create(['name' => 'Area']);
        $this->get('/admin/areas/'.$area->id.'/edit')->assertRedirect(route('login'));
        foreach (['put', 'patch', 'delete'] as $method) {
            $url = '/admin/areas/'.$area->id.($method === 'patch' ? '/status' : '');
            $this->{$method}($url, ['name' => 'Area', 'status' => 'inactive'])->assertRedirect(route('login'));
        }
        $staff = $this->admin();
        $staff->forceFill(['role' => 'staff'])->save();
        $this->actingAs($staff, 'web')->get('/admin/areas/'.$area->id.'/edit')->assertForbidden();
        foreach (['put', 'patch', 'delete'] as $method) {
            $url = '/admin/areas/'.$area->id.($method === 'patch' ? '/status' : '');
            $this->{$method}($url, ['name' => 'Area', 'status' => 'inactive'])->assertForbidden();
        }
        $staff->forceFill(['role' => 'admin'])->save();
        $this->get('/admin/areas/9999/edit')->assertNotFound();
        foreach (['put', 'patch', 'delete'] as $method) {
            $url = '/admin/areas/9999'.($method === 'patch' ? '/status' : '');
            $this->{$method}($url, ['name' => 'Area', 'status' => 'active'])->assertNotFound();
        }
        $this->get('/admin/areas/'.$area->id.'/status')->assertStatus(405);
        $this->get('/admin/areas/'.$area->id)->assertStatus(405);
        $this->app->instance('env', 'local');
        foreach (['put', 'patch', 'delete'] as $method) {
            $url = '/admin/areas/'.$area->id.($method === 'patch' ? '/status' : '');
            $this->{$method}($url, ['name' => 'Area', 'status' => 'inactive'])->assertStatus(419);
        }
    }

    public function test_delete_database_failure_is_safe_and_rolls_back(): void
    {
        Exceptions::fake();
        $area = Area::create(['name' => 'Keep']);
        $this->actingAs($this->admin(), 'web');
        Area::deleting(fn () => throw new QueryException('sqlite', 'fixture SQL', [], new \RuntimeException('fixture failure')));
        try {
            $this->delete('/admin/areas/'.$area->id)->assertRedirect(route('admin.areas.index'));
            $this->get('/admin/areas')->assertOk()->assertSee('Không thể xóa khu vực do ràng buộc dữ liệu hoặc lỗi lưu trữ.')
                ->assertDontSee('fixture failure')->assertDontSee('fixture SQL');
            $this->assertNull($area->fresh()->deleted_at);
            Exceptions::assertReported(QueryException::class);
        } finally {
            Area::flushEventListeners();
        }
    }
}
