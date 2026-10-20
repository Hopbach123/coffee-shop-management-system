<?php

namespace Tests\Feature;

use App\Models\AdminMenu;
use App\Models\User;
use App\Services\AdminMenuManagementService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminMenuMutationTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        foreach (['0001_01_01_000000_create_users_table.php', '2026_10_20_000001_create_admin_menus_table.php'] as $file) {
            (require database_path('migrations/'.$file))->up();
        }
        $this->admin = User::forceCreate(['name' => 'Admin', 'email' => 'mutation@example.test', 'password' => 'test-only-password', 'role' => 'admin', 'status' => 'active']);
        $this->actingAs($this->admin);
        $this->withoutVite();
    }

    private function menu(array $data = []): AdminMenu
    {
        return AdminMenu::create(array_merge(['name' => 'Menu', 'type' => 'group'], $data))->fresh();
    }

    private function token(AdminMenu $menu): array
    {
        return ['expected_fingerprint' => app(AdminMenuManagementService::class)->fingerprint($menu->fresh())];
    }

    public function test_edit_update_type_route_and_icon_preserve_hierarchy_and_order(): void
    {
        $root = $this->menu();
        $menu = $root->children()->create(['name' => 'Child', 'type' => 'group']);
        $menu->sort_order = 7;
        $menu->save();
        $created = $menu->created_at;
        $this->get('/admin/menus/'.$menu->id.'/edit')->assertOk()->assertSee('Child')->assertDontSee('name="parent_id"', false)->assertDontSee('name="sort_order"', false);
        $this->put('/admin/menus/'.$menu->id, $this->token($menu) + ['name' => ' Changed ', 'type' => 'link', 'route_name' => 'admin.users.index', 'icon' => 'users'])->assertSessionHasNoErrors()->assertSessionHas('success');
        $menu->refresh();
        $this->assertSame('Changed', $menu->name);
        $this->assertSame('users', $menu->icon);
        $this->assertSame('admin.users.index', $menu->route_name);
        $this->assertSame($root->id, $menu->parent_id);
        $this->assertSame(7, $menu->sort_order);
        $this->assertEquals($created, $menu->created_at);
        $this->put('/admin/menus/'.$menu->id, $this->token($menu) + ['name' => 'Group again', 'type' => 'group'])->assertSessionHasNoErrors();
        $this->assertNull($menu->fresh()->route_name);
    }

    public function test_update_validation_and_children_integrity(): void
    {
        $menu = $this->menu();
        $child = $menu->children()->create(['name' => 'Child', 'type' => 'group']);
        foreach ([['name' => ' '], ['name' => str_repeat('x', 101)], ['type' => 'link', 'route_name' => 'admin.users.edit'],
            ['type' => 'link', 'route_name' => 'staff.home'], ['type' => 'link', 'route_name' => 'admin.home'],
            ['type' => 'link', 'route_name' => 'admin.users.index'], ['icon' => '<svg>'],
            ['parent_id' => $child->id], ['sort_order' => 2], ['created_at' => '2000-01-01'],
            ['permission_key' => 'admin'], ['is_system' => true], ['is_active' => false],
        ] as $bad) {
            $this->put('/admin/menus/'.$menu->id, array_merge($this->token($menu), ['name' => 'Menu', 'type' => 'group'], $bad))->assertSessionHasErrors();
            $this->assertSame('group', $menu->fresh()->type);
            $this->assertSame(2, AdminMenu::count());
        }
    }

    public function test_visibility_is_explicit_repeatable_and_does_not_change_children(): void
    {
        $menu = $this->menu();
        $child = $menu->children()->create(['name' => 'Child', 'type' => 'group']);
        $before = $child->fresh()->toArray();
        foreach ([0, 0, 1] as $status) {
            $this->patch('/admin/menus/'.$menu->id.'/status', $this->token($menu) + ['is_active' => $status])->assertSessionHasNoErrors();
            $this->assertSame((bool) $status, $menu->fresh()->is_active);
            $this->assertSame($before, $child->fresh()->toArray());
        }
        $this->patch('/admin/menus/'.$menu->id.'/status', $this->token($menu) + ['is_active' => 'toggle'])->assertSessionHasErrors('is_active');
        $this->get('/admin/menus')->assertSee('Child');
    }

    public function test_protected_routes_and_ancestors_are_preserved_but_labels_are_editable(): void
    {
        $root = $this->menu();
        foreach (config('admin_menu.protected_routes') as $route) {
            $menu = $root->children()->create(['name' => 'System', 'type' => 'link', 'route_name' => $route]);
            $this->put('/admin/menus/'.$menu->id, $this->token($menu) + ['name' => 'New label', 'type' => 'link', 'route_name' => $route])->assertSessionHasNoErrors();
            $this->put('/admin/menus/'.$menu->id, $this->token($menu) + ['name' => 'Unsafe', 'type' => 'link', 'route_name' => 'admin.users.index'])->assertSessionHasErrors('menu');
            $this->patch('/admin/menus/'.$menu->id.'/status', $this->token($menu) + ['is_active' => 0])->assertSessionHasErrors('menu');
            $this->delete('/admin/menus/'.$menu->id, $this->token($menu) + ['confirm_delete' => 1])->assertSessionHasErrors('menu');
        }
        $this->patch('/admin/menus/'.$root->id.'/status', $this->token($root) + ['is_active' => 0])->assertSessionHasErrors('menu');
        $this->delete('/admin/menus/'.$root->id, $this->token($root) + ['confirm_delete' => 1])->assertSessionHasErrors('menu');
        $this->assertSame(4, AdminMenu::count());
    }

    public function test_delete_requires_confirmation_and_restricts_parents_and_external_foreign_keys(): void
    {
        $root = $this->menu();
        $child = $root->children()->create(['name' => 'Child', 'type' => 'group']);
        $this->delete('/admin/menus/'.$child->id, $this->token($child))->assertSessionHasErrors('confirm_delete');
        $this->delete('/admin/menus/'.$root->id, $this->token($root) + ['confirm_delete' => 1])->assertSessionHasErrors('menu');
        Schema::create('menu_test_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('admin_menus')->restrictOnDelete();
        });
        DB::table('menu_test_references')->insert(['menu_id' => $child->id]);
        $this->delete('/admin/menus/'.$child->id, $this->token($child) + ['confirm_delete' => 1])->assertSessionHasErrors('menu')->assertSessionMissing('success');
        $this->assertSame(2, AdminMenu::count());
        DB::table('menu_test_references')->delete();
        $this->delete('/admin/menus/'.$child->id, $this->token($child) + ['confirm_delete' => 1])->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame(1, AdminMenu::count());
    }

    public function test_stale_forms_do_not_overwrite_newer_data(): void
    {
        $menu = $this->menu();
        $stale = $this->token($menu);
        $menu->name = 'Newer';
        $menu->save();
        $this->put('/admin/menus/'.$menu->id, $stale + ['name' => 'Stale', 'type' => 'group'])->assertSessionHasErrors('menu');
        $this->patch('/admin/menus/'.$menu->id.'/status', $stale + ['is_active' => 0])->assertSessionHasErrors('menu');
        $this->delete('/admin/menus/'.$menu->id, $stale + ['confirm_delete' => 1])->assertSessionHasErrors('menu');
        $this->assertSame('Newer', $menu->fresh()->name);
    }

    public function test_auth_missing_records_and_csrf_are_enforced(): void
    {
        $menu = $this->menu();
        $requests = [['get', '/admin/menus/'.$menu->id.'/edit'], ['put', '/admin/menus/'.$menu->id],
            ['patch', '/admin/menus/'.$menu->id.'/status'], ['delete', '/admin/menus/'.$menu->id]];
        foreach ($requests as [$method, $url]) {
            $this->{$method}(str_replace('/'.$menu->id, '/999', $url))->assertNotFound();
        }
        $this->admin->role = 'staff';
        $this->admin->save();
        foreach ($requests as [$method, $url]) {
            $this->{$method}($url)->assertForbidden();
        }
        auth('web')->logout();
        foreach ($requests as [$method, $url]) {
            $this->{$method}($url)->assertRedirect('/login');
        }
        $this->admin->role = 'admin';
        $this->admin->save();
        $this->actingAs($this->admin);
        $this->app['env'] = 'local';
        try {
            foreach (array_slice($requests, 1) as [$method, $url]) {
                $this->{$method}($url)->assertStatus(419);
            }
        } finally {
            $this->app['env'] = 'testing';
        }
    }
}
