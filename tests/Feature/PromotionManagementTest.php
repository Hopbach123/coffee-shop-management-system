<?php

namespace Tests\Feature;

use App\Models\Promotion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PromotionManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
        (require database_path('migrations/2026_08_14_000008_create_promotions_table.php'))->up();
    }

    private function user(string $role = 'admin'): User
    {
        return User::forceCreate([
            'name' => 'Promotion test user', 'email' => $role.'@example.test',
            'password' => 'test-password', 'role' => $role, 'status' => 'active',
        ]);
    }

    private function payload(array $changes = []): array
    {
        return array_replace([
            'code' => 'cafe10', 'name' => 'Giảm 10%', 'description' => null,
            'discount_type' => 'percentage', 'discount_value' => '10.00',
            'max_discount' => '20000.00', 'minimum_order' => '0',
            'start_at' => '2026-01-01 00:00:00', 'end_at' => '2027-01-01 00:00:00',
            'usage_limit' => null, 'status' => 'active',
        ], $changes);
    }

    private function promotion(array $changes = []): Promotion
    {
        return Promotion::forceCreate(array_replace($this->payload(['code' => 'CAFE10']), $changes));
    }

    public function test_routes_are_admin_only_and_views_render(): void
    {
        $promotion = $this->promotion();
        foreach (['index', 'create', 'store', 'edit', 'update', 'destroy', 'toggle-status'] as $action) {
            $route = app('router')->getRoutes()->getByName('admin.promotions.'.$action);
            $this->assertNotNull($route);
            $this->assertContains('auth:web', $route->gatherMiddleware());
            $this->assertContains('role:admin', $route->gatherMiddleware());
        }
        $endpoints = [
            ['GET', '/admin/promotions'], ['GET', '/admin/promotions/create'],
            ['POST', '/admin/promotions'], ['GET', '/admin/promotions/'.$promotion->id.'/edit'],
            ['PUT', '/admin/promotions/'.$promotion->id],
            ['PATCH', '/admin/promotions/'.$promotion->id.'/toggle-status'],
            ['DELETE', '/admin/promotions/'.$promotion->id],
        ];
        foreach ($endpoints as [$method, $url]) {
            $this->call($method, $url, $this->payload())->assertRedirect('/login');
        }
        $this->actingAs($this->user('staff'), 'web');
        foreach ($endpoints as [$method, $url]) {
            $this->call($method, $url, $this->payload())->assertForbidden();
        }
        $this->actingAs($this->user(), 'web')->withoutVite();
        $this->get('/admin/promotions')->assertOk()->assertViewIs('admin.promotions.index')->assertSee('CAFE10');
        $this->get('/admin/promotions/create')->assertOk()->assertViewIs('admin.promotions.create')->assertSee('Giới hạn lượt dùng');
        $this->get('/admin/promotions/'.$promotion->id.'/edit')->assertOk()->assertViewIs('admin.promotions.edit')->assertSee('CAFE10');
    }

    public function test_create_update_normalize_code_and_keep_history_fields_private(): void
    {
        $this->actingAs($this->user(), 'web');
        $this->post('/admin/promotions', $this->payload([
            'code' => ' cafe10 ', 'used_count' => 500, 'id' => 9999,
            'deleted_at' => '2000-01-01', 'created_at' => '2000-01-01',
        ]))->assertRedirect(route('admin.promotions.index'))->assertSessionHasNoErrors();
        $promotion = Promotion::firstOrFail();
        $this->assertSame('CAFE10', $promotion->code);
        $this->assertSame(0, $promotion->used_count);
        $this->assertNull($promotion->deleted_at);
        $this->assertNotSame(9999, $promotion->id);

        $this->post('/admin/promotions', $this->payload(['code' => 'Cafe10']))->assertSessionHasErrors('code');
        $this->put('/admin/promotions/'.$promotion->id, $this->payload(['code' => 'cafe10']))
            ->assertRedirect(route('admin.promotions.index'))->assertSessionHasNoErrors();
        $this->put('/admin/promotions/'.$promotion->id, $this->payload([
            'code' => ' cafe20 ', 'discount_type' => 'fixed',
            'discount_value' => '5000.00', 'max_discount' => '9999.00',
            'used_count' => 200,
        ]))->assertRedirect(route('admin.promotions.index'))->assertSessionHasNoErrors();
        $promotion->refresh();
        $this->assertSame('CAFE20', $promotion->code);
        $this->assertSame('fixed', $promotion->discount_type);
        $this->assertNull($promotion->max_discount);
        $this->assertSame(0, $promotion->used_count);
    }

    public function test_validation_rejects_invalid_fields(): void
    {
        $this->actingAs($this->user(), 'web');
        foreach ([
            ['code', ' ', 'code'], ['name', '', 'name'],
            ['discount_type', 'bogus', 'discount_type'],
            ['discount_value', '-1', 'discount_value'],
            ['discount_value', '1.234', 'discount_value'],
            ['max_discount', '-1', 'max_discount'],
            ['minimum_order', '-1', 'minimum_order'],
            ['end_at', '2026-01-01 00:00:00', 'end_at'],
            ['usage_limit', '0', 'usage_limit'],
            ['status', 'available', 'status'],
        ] as [$field, $value, $error]) {
            $this->post('/admin/promotions', $this->payload([$field => $value]))->assertSessionHasErrors($error);
        }
        $this->assertDatabaseCount('promotions', 0);
    }

    public function test_toggle_and_soft_delete(): void
    {
        $promotion = $this->promotion();
        $this->actingAs($this->user(), 'web');
        foreach (['inactive', 'active'] as $expected) {
            $this->patch('/admin/promotions/'.$promotion->id.'/toggle-status')
                ->assertRedirect(route('admin.promotions.index'));
            $this->assertSame($expected, $promotion->fresh()->status);
        }
        $this->delete('/admin/promotions/'.$promotion->id)->assertRedirect(route('admin.promotions.index'));
        $this->assertSoftDeleted('promotions', ['id' => $promotion->id]);
        $this->assertNull(Promotion::find($promotion->id));
    }

    public function test_search_and_pagination_keep_query(): void
    {
        $this->actingAs($this->user(), 'web')->withoutVite();
        $this->get('/admin/promotions')->assertOk()->assertSee('Chưa có khuyến mãi');
        for ($i = 1; $i <= 16; $i++) {
            $this->promotion(['code' => 'SAVE'.$i, 'name' => 'Summer '.$i]);
        }
        $this->promotion(['code' => 'OTHER', 'name' => 'Winter']);
        $this->get('/admin/promotions?search=Missing')->assertOk()->assertSee('Không tìm thấy khuyến mãi');
        $this->get('/admin/promotions?search=Summer')->assertOk()->assertSee('search=Summer&amp;page=2', false);
        $this->get('/admin/promotions?search=Summer&page=2')->assertOk()->assertSee('Summer 16')->assertDontSee('Winter');
        $this->get('/admin/promotions?search=OTHER')->assertOk()->assertSee('Winter')->assertDontSee('Summer 1');
    }
}
