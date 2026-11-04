<?php

namespace Tests\Feature;

use App\Models\AdminMenu;
use App\Models\User;
use App\Services\AdminMenuManagementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminMenuManagementTest extends TestCase
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
        $this->admin = User::forceCreate(['name' => 'Admin', 'email' => 'menu@example.test', 'password' => 'test-password', 'role' => 'admin', 'status' => 'active']);
        $this->withoutVite();
    }

    public function test_empty_listing_and_create_form(): void
    {
        $this->actingAs($this->admin)->get('/admin/menus')->assertOk()->assertSee('Chưa có Menu nào.');
        $this->get('/admin/menus/create')->assertOk()->assertSee('name="_token"', false)->assertSee('admin.users.index')->assertDontSee('<option value="admin.home"', false);
    }

    public function test_create_root_child_defaults_and_duplicate_routes_without_modifying_parent(): void
    {
        $this->actingAs($this->admin)->post('/admin/menus', ['name' => '  Root  ', 'type' => 'group'])->assertRedirect('/admin/menus')->assertSessionHas('success');
        $root = AdminMenu::firstOrFail();
        $this->assertSame('Root', $root->name);
        $root->is_active = false;
        $root->save();
        $before = $root->fresh()->toArray();
        for ($i = 0; $i < 2; $i++) {
            $this->post('/admin/menus', ['name' => 'Child', 'type' => 'link', 'route_name' => 'admin.users.index', 'parent_id' => $root->id, 'sort_order' => 7, 'icon' => 'users'])->assertSessionHasNoErrors();
        }
        $this->assertSame($before, $root->fresh()->toArray());
        $child = AdminMenu::where('type', 'link')->firstOrFail();
        $this->assertTrue($child->is_active);
        $this->assertNull($child->permission_key);
        $this->assertSame(7, $child->sort_order);
        $this->assertSame(3, AdminMenu::count());
        $this->get('/admin/menus')->assertSee('Đang tắt')->assertSee('Cấp 2');
    }

    public function test_invalid_input_never_creates_a_record(): void
    {
        $this->actingAs($this->admin);
        foreach ([['name' => ' '], ['name' => str_repeat('x', 101)], ['type' => 'html'],
            ['type' => 'link'], ['type' => 'link', 'route_name' => 'admin.users.edit'],
            ['type' => 'link', 'route_name' => 'admin.home'], ['type' => 'link', 'route_name' => 'staff.home'],
            ['type' => 'link', 'route_name' => 'javascript:alert(1)'], ['route_name' => 'admin.users.index'],
            ['parent_id' => 999], ['icon' => '<svg>'], ['sort_order' => -1], ['sort_order' => '1.5'],
            ['permission_key' => 'superadmin'], ['is_active' => false], ['is_system' => true], ['id' => 10],
        ] as $invalid) {
            $this->post('/admin/menus', array_merge(['name' => 'Invalid', 'type' => 'group'], $invalid))->assertSessionHasErrors();
            $this->assertSame(0, AdminMenu::count());
        }
    }

    public function test_depth_and_parent_type_are_checked_server_side(): void
    {
        $root = AdminMenu::create(['name' => 'Root', 'type' => 'group']);
        $second = $root->children()->create(['name' => 'Second', 'type' => 'group']);
        $third = $second->children()->create(['name' => 'Third', 'type' => 'group']);
        $leaf = AdminMenu::create(['name' => 'Leaf', 'type' => 'link', 'route_name' => 'admin.users.index']);
        $this->actingAs($this->admin);
        foreach ([$third->id, $leaf->id] as $parentId) {
            $this->post('/admin/menus', ['name' => 'Invalid', 'type' => 'group', 'parent_id' => $parentId])->assertSessionHasErrors('parent_id');
        }
        $this->assertSame(4, AdminMenu::count());
        $this->post('/admin/menus', ['name' => 'Valid third', 'type' => 'group', 'parent_id' => $second->id])->assertSessionHasNoErrors();
    }

    public function test_management_tree_has_one_query_stable_order_and_escaped_labels(): void
    {
        $root = AdminMenu::create(['name' => '<script>root</script>', 'type' => 'group']);
        $later = $root->children()->create(['name' => 'Later', 'type' => 'group']);
        $earlier = $root->children()->create(['name' => 'Earlier', 'type' => 'group']);
        $earlier->sort_order = 0;
        $later->sort_order = 2;
        $later->save();
        DB::enableQueryLog();
        $rows = app(AdminMenuManagementService::class)->rows();
        $this->assertCount(1, DB::getQueryLog());
        DB::disableQueryLog();
        DB::flushQueryLog();
        $this->assertSame([$root->id, $earlier->id, $later->id], array_map(fn ($row) => $row['menu']->id, $rows));
        $this->actingAs($this->admin)->get('/admin/menus')->assertOk()->assertDontSee('<script>root</script>', false)->assertSee('&lt;script&gt;root&lt;/script&gt;', false);
    }

    public function test_corrupt_cycle_is_listed_once_and_cannot_be_a_parent(): void
    {
        $a = AdminMenu::create(['name' => 'A', 'type' => 'group']);
        $b = $a->children()->create(['name' => 'B', 'type' => 'group']);
        $a->parent_id = $b->id;
        $a->save();
        $rows = app(AdminMenuManagementService::class)->rows();
        $this->assertCount(2, $rows);
        $this->assertTrue($rows[0]['invalid']);
        $this->actingAs($this->admin)->post('/admin/menus', ['name' => 'Invalid', 'type' => 'group', 'parent_id' => $a->id])->assertSessionHasErrors('parent_id');
    }

    public function test_all_three_endpoints_are_admin_only(): void
    {
        foreach (['/admin/menus', '/admin/menus/create'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
        $this->post('/admin/menus')->assertRedirect('/login');
        $this->admin->role = 'staff';
        $this->admin->save();
        $this->actingAs($this->admin);
        $this->get('/admin/menus')->assertForbidden();
        $this->get('/admin/menus/create')->assertForbidden();
        $this->post('/admin/menus')->assertForbidden();
    }

    public function test_database_unavailable_does_not_leak_sql_or_claim_success(): void
    {
        Schema::drop('admin_menus');
        $this->actingAs($this->admin)->get('/admin/menus')->assertStatus(503)->assertDontSee('SQLSTATE');
        $this->get('/admin/menus/create')->assertStatus(503);
        $this->post('/admin/menus', ['name' => 'Attempt', 'type' => 'group'])->assertSessionHasErrors('menu')->assertSessionMissing('success');
    }

    public function test_store_rejects_missing_csrf_when_test_bypass_is_disabled(): void
    {
        $this->actingAs($this->admin);
        $this->app['env'] = 'local';
        try {
            $this->post('/admin/menus', ['name' => 'No token', 'type' => 'group'])->assertStatus(419);
            $this->assertSame(0, AdminMenu::count());
        } finally {
            $this->app['env'] = 'testing';
        }
    }
}
