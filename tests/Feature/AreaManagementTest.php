<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Tests\TestCase;

class AreaManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        foreach (['0001_01_01_000000_create_users_table.php', '2026_08_14_000002_create_areas_table.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        $this->withoutVite();
    }

    private function admin(): User
    {
        return User::forceCreate(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'test-only-password', 'role' => 'admin', 'status' => 'active']);
    }

    public function test_guest_and_staff_cannot_list_open_form_or_store(): void
    {
        foreach (['/admin/areas', '/admin/areas/create'] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
        $this->post('/admin/areas', ['name' => 'Area'])->assertRedirect(route('login'));
        $staff = $this->admin();
        $staff->forceFill(['role' => 'staff'])->save();
        $this->actingAs($staff, 'web');
        foreach (['/admin/areas', '/admin/areas/create'] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->post('/admin/areas', ['name' => 'Area'])->assertForbidden();
        $this->assertDatabaseCount('areas', 0);
    }

    public function test_admin_creates_trimmed_duplicate_names_using_database_defaults_and_whitelist(): void
    {
        $this->actingAs($this->admin(), 'web')->get('/admin/areas/create')->assertOk()->assertSee('name="_token"', false)->assertDontSee('name="status"', false);
        DB::enableQueryLog();
        for ($i = 0; $i < 2; $i++) {
            $this->post('/admin/areas', ['name' => '  Tầng trệt  ', 'description' => null, 'status' => 'inactive', 'id' => 9999,
                'created_at' => '2000-01-01', 'updated_at' => '2000-01-01', 'deleted_at' => '2000-01-01'])
                ->assertRedirect(route('admin.areas.index'))->assertSessionHas('success', 'Đã thêm khu vực.');
        }
        foreach (DB::getQueryLog() as $query) {
            $this->assertDoesNotMatchRegularExpression('/\b(cafe_tables|reservations)\b/i', $query['query']);
        }
        DB::disableQueryLog();
        DB::flushQueryLog();
        $this->assertDatabaseCount('areas', 2);
        foreach (Area::all() as $area) {
            $this->assertSame('Tầng trệt', $area->name);
            $this->assertSame('active', $area->status);
            $this->assertNull($area->description);
            $this->assertNull($area->deleted_at);
            $this->assertNotSame(9999, $area->id);
            $this->assertNotSame('2000-01-01', $area->created_at->format('Y-m-d'));
        }
        $this->get('/admin/areas')->assertOk()->assertSee('Đã thêm khu vực.');
    }

    public function test_listing_search_pagination_sort_nulls_and_escaping(): void
    {
        $this->actingAs($this->admin(), 'web')->get('/admin/areas')->assertOk()->assertSee('Chưa có khu vực');
        $ids = [];
        for ($i = 0; $i < 16; $i++) {
            $ids[] = Area::forceCreate(['name' => 'Floor '.$i, 'created_at' => '2026-01-01 12:00:00'])->id;
        }
        Area::create(['name' => 'Unrelated', 'description' => 'Floor in description only']);
        Area::create(['name' => 'Floor deleted'])->delete();
        $this->get('/admin/areas?search=floor')->assertOk()->assertSee('—')->assertSee('01/01/2026 12:00')
            ->assertSee('search=floor&amp;page=2', false)
            ->assertViewHas('areas', fn ($areas) => $areas->total() === 16 && $areas->first()->id === $ids[15]);
        $this->get('/admin/areas?search=floor&page=2')->assertOk()
            ->assertViewHas('areas', fn ($areas) => $areas->pluck('id')->all() === [$ids[0]] && str_contains($areas->previousPageUrl(), 'search=floor'));
        $this->get('/admin/areas?search=Missing')->assertOk()->assertSee('Không tìm thấy khu vực');
        Area::forceCreate(['name' => '<script>alert(1)</script>', 'status' => 'inactive']);
        $this->get('/admin/areas')->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false)->assertSee('Ngừng hoạt động')->assertSee('Đang hoạt động');
    }

    public function test_validation_limits_whitespace_and_old_values(): void
    {
        $this->actingAs($this->admin(), 'web');
        foreach (['', '   ', str_repeat('a', 101), ['bad']] as $name) {
            $this->post('/admin/areas', ['name' => $name])->assertSessionHasErrors('name');
        }
        $this->post('/admin/areas', ['name' => 'Valid', 'description' => ['bad']])->assertSessionHasErrors('description');
        $this->assertDatabaseCount('areas', 0);
        $this->from('/admin/areas/create')->post('/admin/areas', ['name' => '', 'description' => 'Retained'])->assertStatus(302);
        $this->get('/admin/areas/create')->assertOk()->assertSee('Retained')->assertSee('name-error');
        $this->post('/admin/areas', ['name' => str_repeat('a', 100)])->assertRedirect(route('admin.areas.index'));
        foreach (['search[]=bad', 'search='.str_repeat('a', 101)] as $query) {
            $this->get('/admin/areas?'.$query)->assertSessionHasErrors('search');
        }
    }

    public function test_database_failure_is_reported_safely_and_csrf_is_required(): void
    {
        Exceptions::fake();
        $this->actingAs($this->admin(), 'web');
        Area::saving(fn () => throw new QueryException('sqlite', 'fixture SQL', [], new \RuntimeException('fixture failure')));
        try {
            $this->from('/admin/areas/create')->post('/admin/areas', ['name' => 'Retained'])->assertRedirect('/admin/areas/create');
            $this->get('/admin/areas/create')->assertOk()->assertSee('Không thể thêm khu vực.')->assertSee('value="Retained"', false)
                ->assertDontSee('fixture failure')->assertDontSee('fixture SQL');
            Exceptions::assertReported(QueryException::class);
        } finally {
            Area::flushEventListeners();
        }
        $this->app->instance('env', 'local');
        $this->post('/admin/areas', ['name' => 'Area'])->assertStatus(419);
        $this->assertDatabaseCount('areas', 0);
    }
}
