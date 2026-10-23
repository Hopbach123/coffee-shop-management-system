<?php

namespace Tests\Feature;

use App\Models\Topping;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ToppingManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());

        foreach ([
            '0001_01_01_000000_create_users_table.php',
            '2026_08_14_000004_create_toppings_table.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
    }

    private function user(string $role = 'admin'): User
    {
        return User::forceCreate([
            'name' => 'Topping test user',
            'email' => $role.'@example.test',
            'password' => 'test-password',
            'role' => $role,
            'status' => 'active',
        ]);
    }

    private function topping(array $overrides = []): Topping
    {
        return Topping::forceCreate(array_replace([
            'name' => 'Trân châu',
            'price' => '10000.00',
            'status' => 'available',
        ], $overrides));
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'name' => 'Trân châu',
            'price' => '10000.00',
            'status' => 'available',
        ], $overrides);
    }

    public function test_all_topping_routes_are_admin_only(): void
    {
        $topping = $this->topping();

        foreach (['index', 'create', 'store', 'edit', 'update', 'destroy', 'toggle-status'] as $action) {
            $route = app('router')->getRoutes()->getByName('admin.toppings.'.$action);
            $this->assertNotNull($route);
            $this->assertContains('auth:web', $route->gatherMiddleware());
            $this->assertContains('role:admin', $route->gatherMiddleware());
        }

        $endpoints = [
            ['GET', '/admin/toppings'],
            ['GET', '/admin/toppings/create'],
            ['POST', '/admin/toppings'],
            ['GET', '/admin/toppings/'.$topping->id.'/edit'],
            ['PUT', '/admin/toppings/'.$topping->id],
            ['PATCH', '/admin/toppings/'.$topping->id.'/toggle-status'],
            ['DELETE', '/admin/toppings/'.$topping->id],
        ];

        foreach ($endpoints as [$method, $url]) {
            $this->call($method, $url, $this->payload())->assertRedirect('/login');
        }

        $this->actingAs($this->user('staff'), 'web');
        foreach ($endpoints as [$method, $url]) {
            $this->call($method, $url, $this->payload())->assertForbidden();
        }

        $this->assertDatabaseCount('toppings', 1);
    }

    public function test_admin_can_render_list_create_and_edit(): void
    {
        $this->withoutVite();
        $topping = $this->topping();
        $this->actingAs($this->user(), 'web');

        $this->get('/admin/toppings')->assertOk()->assertViewIs('admin.toppings.index')
            ->assertSee('Trân châu')->assertSee('10.000,00 ₫')->assertSee('Có sẵn');
        $this->get('/admin/toppings/create')->assertOk()->assertViewIs('admin.toppings.create')
            ->assertSee('Tên topping')->assertSee('value="0"', false)
            ->assertDontSee('name="product_id"', false);
        $this->get('/admin/toppings/'.$topping->id.'/edit')->assertOk()
            ->assertViewIs('admin.toppings.edit')
            ->assertSee('value="Trân châu"', false)
            ->assertSee('value="10000.00"', false);
    }

    public function test_create_accepts_zero_positive_and_duplicate_names_without_extra_fields(): void
    {
        $this->actingAs($this->user(), 'web');

        $this->post('/admin/toppings', $this->payload([
            'price' => '0',
            'product_id' => 123,
            'id' => 9999,
            'deleted_at' => '2000-01-01',
            'created_at' => '2000-01-01',
        ]))->assertRedirect(route('admin.toppings.index'))->assertSessionHas('success')->assertSessionHasNoErrors();
        $this->post('/admin/toppings', $this->payload(['price' => '15000.50']))
            ->assertRedirect(route('admin.toppings.index'))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('toppings', 2);
        $this->assertSame(2, Topping::where('name', 'Trân châu')->count());
        $this->assertSame('0.00', Topping::findOrFail(1)->price);
        $this->assertSame('15000.50', Topping::findOrFail(2)->price);
        $this->assertNull(Topping::findOrFail(1)->deleted_at);
        $this->assertFalse(Schema::hasColumn('toppings', 'product_id'));
        $this->assertFalse(Schema::hasTable('product_toppings'));
    }

    public function test_validation_rejects_bad_fields_and_preserves_old_input(): void
    {
        $this->withoutVite();
        $this->actingAs($this->user(), 'web');

        foreach ([
            ['name', '', 'name'],
            ['name', str_repeat('a', 256), 'name'],
            ['price', null, 'price'],
            ['price', 'abc', 'price'],
            ['price', '-0.01', 'price'],
            ['price', '1.234', 'price'],
            ['price', '10000000000', 'price'],
            ['status', 'active', 'status'],
        ] as [$field, $value, $error]) {
            $this->from('/admin/toppings/create')
                ->post('/admin/toppings', $this->payload([$field => $value]))
                ->assertRedirect('/admin/toppings/create')->assertSessionHasErrors($error);
        }

        $invalid = $this->payload(['name' => '', 'price' => '-1']);
        $this->followingRedirects()->from('/admin/toppings/create')
            ->post('/admin/toppings', $invalid)->assertOk()
            ->assertSee('Vui lòng kiểm tra các trường')
            ->assertSee('value="-1"', false);
        $this->assertDatabaseCount('toppings', 0);
    }

    public function test_update_changes_only_approved_fields(): void
    {
        $topping = $this->topping();
        $createdAt = $topping->created_at;
        $this->actingAs($this->user(), 'web');

        $this->put('/admin/toppings/'.$topping->id, $this->payload([
            'name' => 'Kem sữa',
            'price' => '12500.75',
            'status' => 'unavailable',
            'id' => 9999,
            'product_id' => 123,
            'deleted_at' => '2000-01-01',
            'created_at' => '2000-01-01',
        ]))->assertRedirect(route('admin.toppings.index'))->assertSessionHas('success')->assertSessionHasNoErrors();

        $topping->refresh();
        $this->assertSame('Kem sữa', $topping->name);
        $this->assertSame('12500.75', $topping->price);
        $this->assertSame('unavailable', $topping->status);
        $this->assertTrue($createdAt->equalTo($topping->created_at));
        $this->assertNull($topping->deleted_at);
        $this->assertNotSame(9999, $topping->id);
    }

    public function test_admin_can_toggle_both_statuses_and_soft_delete(): void
    {
        $this->withoutVite();
        $topping = $this->topping();
        $this->actingAs($this->user(), 'web');

        foreach (['unavailable', 'available'] as $expected) {
            $this->patch('/admin/toppings/'.$topping->id.'/toggle-status')
                ->assertRedirect(route('admin.toppings.index'))->assertSessionHas('success');
            $this->assertSame($expected, $topping->fresh()->status);
        }

        $this->delete('/admin/toppings/'.$topping->id)
            ->assertRedirect(route('admin.toppings.index'));
        $this->assertSoftDeleted('toppings', ['id' => $topping->id]);
        $this->assertNull(Topping::find($topping->id));
        $this->assertNotNull(Topping::withTrashed()->find($topping->id));
        $this->get('/admin/toppings')->assertOk()->assertDontSee('Trân châu');
        $this->get('/admin/toppings/'.$topping->id.'/edit')->assertNotFound();
    }

    public function test_search_pagination_and_empty_states(): void
    {
        $this->withoutVite();
        $this->actingAs($this->user(), 'web');

        $this->get('/admin/toppings')->assertOk()->assertSee('Chưa có topping');
        for ($index = 1; $index <= 16; $index++) {
            $this->topping(['name' => 'Milk '.$index]);
        }
        $this->topping(['name' => 'Pearl']);
        $this->topping(['name' => 'Milk deleted'])->delete();

        $this->get('/admin/toppings?search=Missing')->assertOk()->assertSee('Không tìm thấy topping');
        $this->get('/admin/toppings?search=Milk')->assertOk()
            ->assertSee('search=Milk&amp;page=2', false)->assertDontSee('Milk deleted');
        $this->get('/admin/toppings?search=Milk&page=2')->assertOk()
            ->assertSee('Milk 16')->assertDontSee('Pearl');
    }
}
