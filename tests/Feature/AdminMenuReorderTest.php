<?php

namespace Tests\Feature;

use App\Models\AdminMenu;
use App\Models\User;
use App\Services\AdminMenuManagementService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminMenuReorderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        foreach (['0001_01_01_000000_create_users_table.php', '2026_10_20_000001_create_admin_menus_table.php'] as $file) {
            (require database_path('migrations/'.$file))->up();
        }
        $this->actingAs(User::forceCreate(['name' => 'Admin', 'email' => 'sort@example.test', 'password' => 'test-password', 'role' => 'admin', 'status' => 'active']));
        $this->withoutVite();
    }

    private function menu(string $name, ?AdminMenu $parent = null, string $type = 'group', ?string $route = null): AdminMenu
    {
        $menu = new AdminMenu(['name' => $name, 'type' => $type, 'route_name' => $route]);
        $menu->parent_id = $parent?->id;
        $menu->save();

        return $menu->fresh();
    }

    private function payload(): array
    {
        $menus = AdminMenu::orderBy('id')->get();

        return ['expected_tree' => app(AdminMenuManagementService::class)->treeFingerprint($menus),
            'items' => $menus->map(fn ($menu) => $menu->only(['id', 'parent_id', 'sort_order']))->all()];
    }

    public function test_same_parent_order_is_saved_without_touching_other_groups_or_fields(): void
    {
        $a = $this->menu('A');
        $b = $this->menu('B');
        $child = $this->menu('Unrelated', $a);
        $beforeChild = $child->toArray();
        $beforeA = $a->only(['name', 'route_name', 'type', 'icon', 'permission_key', 'is_active', 'created_at']);
        $payload = $this->payload();
        $payload['items'][0]['sort_order'] = 1;
        $payload['items'][1]['sort_order'] = 0;
        $this->patchJson('/admin/menus/reorder', $payload)->assertOk();
        $this->assertSame([$b->id, $a->id], AdminMenu::roots()->ordered()->pluck('id')->all());
        $this->assertSame($beforeChild, $child->fresh()->toArray());
        $this->assertEquals($beforeA, $a->fresh()->only(array_keys($beforeA)));
        $this->assertSame(3, AdminMenu::count());
        $this->get('/admin/menus')->assertOk()->assertSee('data-menu-sort', false)
            ->assertSeeInOrder(['data-label="Tên / cấp">B <small>', 'data-label="Tên / cấp">A <small>'], false);
    }

    public function test_cross_parent_root_and_inactive_group_moves_preserve_subtree(): void
    {
        $a = $this->menu('A');
        $b = $this->menu('B');
        $b->is_active = false;
        $b->save();
        $child = $this->menu('Child', $a);
        $leaf = $this->menu('Leaf', $child, 'link', 'admin.users.index');
        $beforeLeaf = $leaf->toArray();
        $payload = $this->payload();
        $payload['items'][2]['parent_id'] = $b->id;
        $this->patchJson('/admin/menus/reorder', $payload)->assertOk();
        $this->assertSame($b->id, $child->fresh()->parent_id);
        $this->assertFalse($b->fresh()->is_active);
        $this->assertSame($beforeLeaf, $leaf->fresh()->toArray());
        $payload = $this->payload();
        $payload['items'][2]['parent_id'] = null;
        $payload['items'][2]['sort_order'] = 99;
        $this->patchJson('/admin/menus/reorder', $payload)->assertOk();
        $this->assertNull($child->fresh()->parent_id);
    }

    public function test_cycles_depth_bad_parent_and_missing_duplicate_unknown_ids_are_atomic(): void
    {
        $a = $this->menu('A');
        $b = $this->menu('B', $a);
        $c = $this->menu('C', $b);
        $link = $this->menu('Link', null, 'link', 'admin.users.index');
        $payload = $this->payload();
        $before = AdminMenu::orderBy('id')->get()->toJson();
        $variants = [];
        foreach ([$a->id, $b->id, $c->id, 999, $link->id] as $parent) {
            $bad = $payload;
            $bad['items'][0]['parent_id'] = $parent;
            $variants[] = $bad;
        }
        $bad = $payload;
        $bad['items'][3]['parent_id'] = $c->id; // fourth level
        $variants[] = $bad;
        $bad = $payload;
        array_pop($bad['items']);
        $variants[] = $bad;
        $bad = $payload;
        $bad['items'][] = $bad['items'][0];
        $variants[] = $bad;
        $bad = $payload;
        $bad['items'][0]['id'] = 999;
        $variants[] = $bad;
        $bad = $payload;
        $bad['items'][0]['name'] = 'Hacked';
        $variants[] = $bad;
        $bad = $payload;
        $bad['items'][0]['sort_order'] = -1;
        $variants[] = $bad;
        foreach ($variants as $bad) {
            $this->patchJson('/admin/menus/reorder', $bad)->assertUnprocessable();
            $this->assertSame($before, AdminMenu::orderBy('id')->get()->toJson());
        }
    }

    public function test_protected_menu_and_ancestor_parent_cannot_change_but_sibling_order_can(): void
    {
        $root = $this->menu('Root');
        $other = $this->menu('Other');
        $system = $this->menu('System', $root, 'link', 'admin.menus.index');
        foreach ([0, 2] as $index) {
            $payload = $this->payload();
            $payload['items'][$index]['parent_id'] = $other->id;
            $this->patchJson('/admin/menus/reorder', $payload)->assertUnprocessable();
        }
        $payload = $this->payload();
        $payload['items'][0]['sort_order'] = 1;
        $this->patchJson('/admin/menus/reorder', $payload)->assertOk();
        $this->assertSame($root->id, $system->fresh()->parent_id);
    }

    public function test_stale_request_after_reorder_create_update_status_and_delete_is_rejected(): void
    {
        $a = $this->menu('A');
        $b = $this->menu('B');
        $payload = $this->payload();
        $changed = $payload;
        $changed['items'][0]['sort_order'] = 1;
        $this->patchJson('/admin/menus/reorder', $changed)->assertOk();
        $this->patchJson('/admin/menus/reorder', $payload)->assertUnprocessable();
        foreach (['create', 'update', 'status', 'delete'] as $action) {
            $payload = $this->payload();
            $service = app(AdminMenuManagementService::class);
            if ($action === 'create') {
                $new = $service->create(['name' => 'New', 'type' => 'group']);
            } else {
                $target = $action === 'delete' ? $new : $a;
                $data = ['expected_fingerprint' => $service->fingerprint($target->fresh())];
                $service->mutate($target->id, $action, $data + ['name' => 'Edited', 'type' => 'group', 'is_active' => 0]);
            }
            $this->patchJson('/admin/menus/reorder', $payload)->assertUnprocessable();
        }
    }

    public function test_database_failure_rolls_back_earlier_writes(): void
    {
        $a = $this->menu('A');
        $this->menu('B');
        $before = AdminMenu::orderBy('id')->get()->toJson();
        DB::unprepared('CREATE TRIGGER fail_menu_sort BEFORE UPDATE ON admin_menus WHEN NEW.id = '.$a->id." BEGIN SELECT RAISE(ABORT, 'test failure'); END");
        $payload = $this->payload();
        $payload['items'][0]['sort_order'] = 1;
        $this->patchJson('/admin/menus/reorder', $payload)->assertStatus(503)->assertDontSee('test failure');
        $this->assertSame($before, AdminMenu::orderBy('id')->get()->toJson());
    }

    public function test_noop_auth_and_csrf(): void
    {
        $this->menu('A');
        $payload = $this->payload();
        $before = AdminMenu::first()->toArray();
        $this->patchJson('/admin/menus/reorder', $payload)->assertOk();
        $this->assertSame($before, AdminMenu::first()->toArray());
        $user = auth()->user();
        $user->role = 'staff';
        $user->save();
        $this->patchJson('/admin/menus/reorder', $payload)->assertForbidden();
        auth()->logout();
        $this->patchJson('/admin/menus/reorder', $payload)->assertUnauthorized();
        $user->role = 'admin';
        $user->save();
        $this->actingAs($user);
        $this->app['env'] = 'local';
        try {
            $this->patchJson('/admin/menus/reorder', $payload)->assertStatus(419);
        } finally {
            $this->app['env'] = 'testing';
        }
    }
}
