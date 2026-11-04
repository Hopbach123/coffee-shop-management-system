<?php

namespace Tests\Feature;

use App\Models\AdminMenu;
use App\Models\User;
use App\Services\AdminMenuManagementService;
use App\Services\AdminMenuSidebarService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DynamicAdminSidebarTest extends TestCase
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
        $this->admin = User::forceCreate(['name' => 'Admin', 'email' => 'sidebar@example.test', 'password' => 'test-password', 'role' => 'admin', 'status' => 'active']);
        $this->actingAs($this->admin);
        $this->withoutVite();
    }

    private function menu(string $name, ?AdminMenu $parent = null, ?string $route = null): AdminMenu
    {
        $menu = new AdminMenu(['name' => $name, 'type' => $route === null ? 'group' : 'link', 'route_name' => $route]);
        $menu->parent_id = $parent?->id;
        $menu->save();

        return $menu->fresh();
    }

    private function tree(?string $route = null, ?User $user = null): array
    {
        return app(AdminMenuSidebarService::class)->build($user ?? $this->admin, $route)['tree'];
    }

    public function test_empty_or_missing_table_keeps_admin_recovery_and_never_seeds(): void
    {
        $this->get('/admin/menus')->assertOk()->assertSee('Chưa có menu hiển thị.')->assertSee('Quản lý menu');
        $this->assertSame(0, AdminMenu::count());
        Schema::drop('admin_menus');
        $this->get('/admin/menus')->assertStatus(503)->assertSee('Quản lý menu')->assertSee('Điều hướng tạm thời chưa sẵn sàng.')->assertDontSee('SQLSTATE');
    }

    public function test_visible_sorted_tree_hides_inactive_branches_empty_groups_and_unknown_permissions(): void
    {
        $root = $this->menu('Root');
        $first = $this->menu('First', $root, 'admin.users.index');
        $second = $this->menu('Second', $root, 'admin.areas.index');
        $first->sort_order = 9;
        $first->save();
        $empty = $this->menu('Empty');
        $inactive = $this->menu('Inactive');
        $this->menu('Hidden branch', $inactive, 'admin.tables.index');
        $inactive->is_active = false;
        $inactive->save();
        $unknown = $this->menu('Unknown permission', null, 'admin.customers.index');
        $unknown->permission_key = 'not-approved';
        $unknown->save();
        $restrictedGroup = $this->menu('Restricted group');
        $this->menu('Restricted descendant', $restrictedGroup, 'admin.tables.index');
        $restrictedGroup->permission_key = 'unknown-group-permission';
        $restrictedGroup->save();
        $tree = $this->tree();
        $this->assertCount(1, $tree);
        $this->assertSame([$second->id, $first->id], array_column($tree[0]['children'], 'id'));
        $this->assertSame('Root', $tree[0]['name']);
    }

    public function test_route_registry_does_not_override_extra_middleware_or_missing_routes(): void
    {
        $service = app(AdminMenuSidebarService::class);
        $this->assertTrue($service->routeAllowed($this->admin, 'admin.users.index'));
        $route = Route::getRoutes()->getByName('admin.users.index');
        $route->middleware('role:staff');
        // Simulate a newly booted route definition, not Laravel's previously gathered cache.
        $route->computedMiddleware = null;
        $this->assertFalse($service->routeAllowed($this->admin, 'admin.users.index'));
        config(['admin_menu.routes' => ['admin.never-created.index', 'admin.users.edit', 'logout']]);
        $this->assertFalse($service->routeAllowed($this->admin, 'admin.never-created.index'));
        $this->assertFalse($service->routeAllowed($this->admin, 'admin.users.edit'));
        $this->assertFalse($service->routeAllowed($this->admin, 'logout'));
    }

    public function test_invalid_routes_orphans_cycles_and_children_of_links_do_not_render(): void
    {
        foreach (['admin.users.edit', 'staff.home', 'logout', 'missing', 'javascript:alert(1)'] as $route) {
            $this->menu('Invalid route', null, $route);
        }
        $a = $this->menu('Cycle A');
        $b = $this->menu('Cycle B', $a);
        $a->parent_id = $b->id;
        $a->save();
        $leaf = $this->menu('Safe leaf', null, 'admin.users.index');
        $this->menu('Invalid child', $leaf, 'admin.areas.index');
        $tree = $this->tree();
        $this->assertCount(1, $tree);
        $this->assertSame('Safe leaf', $tree[0]['name']);
        $this->assertSame([], $tree[0]['children']);
    }

    public function test_exact_match_precedes_module_match_only_one_duplicate_active_and_ancestors_open(): void
    {
        $root = $this->menu('Root');
        $nested = $this->menu('Nested', $root);
        $first = $this->menu('Users first', $nested, 'admin.users.index');
        $this->menu('Users duplicate', $nested, 'admin.users.index');
        $tree = $this->tree('admin.users.edit');
        $this->assertTrue($tree[0]['active']);
        $this->assertTrue($tree[0]['children'][0]['active']);
        $this->assertSame([true, false], array_column($tree[0]['children'][0]['children'], 'active'));
        $response = $this->get('/admin/users/create')->assertOk();
        $response->assertSee('data-default-open="1"', false);
        $html = Blade::render('<x-internal.admin-sidebar />');
        $this->assertStringContainsString('aria-controls="sidebar-group-'.$root->id.'"', $html);
        $this->assertStringContainsString('aria-current="page"', $html);
    }

    public function test_rendering_escapes_labels_and_permission_filtering_is_not_an_auth_bypass(): void
    {
        $this->menu('<script>secret-label</script>', null, 'admin.users.index');
        $this->get('/admin/menus')->assertOk()->assertSee('&lt;script&gt;secret-label&lt;/script&gt;', false)->assertDontSee('<script>secret-label</script>', false);
        $staff = User::forceCreate(['name' => 'Staff', 'email' => 'staff-sidebar@example.test', 'password' => 'test-password', 'role' => 'staff', 'status' => 'active']);
        DB::enableQueryLog();
        $this->assertSame([], $this->tree(null, $staff));
        $this->assertCount(0, DB::getQueryLog());
        DB::disableQueryLog();
        DB::flushQueryLog();
        $this->actingAs($staff)->get('/admin/menus')->assertForbidden();
        $this->assertSame('', trim(strip_tags(Blade::render('<x-internal.admin-sidebar />'))));
        auth()->logout();
        $this->get('/admin/menus')->assertRedirect('/login');
        $this->assertSame([], app(AdminMenuSidebarService::class)->build(null, 'admin.home')['tree']);
    }

    public function test_one_read_query_no_writes_and_management_snapshot_is_reused(): void
    {
        $root = $this->menu('Root');
        for ($i = 0; $i < 20; $i++) {
            $this->menu('Item '.$i, $root, 'admin.users.index');
        }
        DB::enableQueryLog();
        $this->tree('admin.users.index');
        $this->assertCount(1, DB::getQueryLog());
        DB::flushQueryLog();
        $this->get('/admin/menus')->assertOk();
        $queries = array_filter(DB::getQueryLog(), fn ($query) => str_contains($query['query'], '"admin_menus"'));
        $this->assertCount(1, $queries);
        foreach ($queries as $query) {
            $this->assertStringStartsWith('select', strtolower($query['query']));
        }
        DB::disableQueryLog();
        DB::flushQueryLog();
    }

    public function test_sidebar_reflects_create_update_toggle_reparent_sort_and_delete_on_next_request(): void
    {
        $service = app(AdminMenuManagementService::class);
        $root = $service->create(['name' => 'Root', 'type' => 'group']);
        $other = $service->create(['name' => 'Other', 'type' => 'group']);
        $child = $service->create(['name' => 'Initial', 'type' => 'link', 'route_name' => 'admin.users.index', 'parent_id' => $root->id]);
        $this->get('/admin/menus')->assertOk()->assertViewHas('rows');
        $service->mutate($child->id, 'update', ['expected_fingerprint' => $service->fingerprint($child->fresh()), 'name' => 'Renamed', 'type' => 'link', 'route_name' => 'admin.areas.index', 'icon' => 'chart']);
        $this->get('/admin/menus')->assertOk()->assertSee('Renamed');
        $menus = AdminMenu::orderBy('id')->get();
        $payload = ['expected_tree' => $service->treeFingerprint($menus), 'items' => $menus->map(fn ($m) => $m->only(['id', 'parent_id', 'sort_order']))->all()];
        $payload['items'][2]['parent_id'] = $other->id;
        $this->patchJson('/admin/menus/reorder', $payload)->assertOk();
        $this->get('/admin/menus')->assertOk();
        $tree = $this->tree();
        $this->assertSame('Other', $tree[0]['name']);
        $this->assertSame('Renamed', $tree[0]['children'][0]['name']);
        $service->mutate($child->id, 'status', ['expected_fingerprint' => $service->fingerprint($child->fresh()), 'is_active' => 0]);
        $this->get('/admin/menus')->assertOk();
        $this->assertSame([], $this->tree());
        $service->mutate($child->id, 'delete', ['expected_fingerprint' => $service->fingerprint($child->fresh())]);
        $this->get('/admin/menus')->assertOk();
        $this->assertSame([], $this->tree());
    }
}
