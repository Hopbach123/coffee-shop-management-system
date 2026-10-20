<?php

namespace Tests\Feature;

use App\Models\AdminMenu;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminMenuArchitectureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        (require database_path('migrations/2026_10_20_000001_create_admin_menus_table.php'))->up();
    }

    public function test_defaults_relationships_ordering_and_nonunique_routes(): void
    {
        $root = AdminMenu::create(['name' => 'Overview', 'type' => 'group']);
        $root->refresh();
        $this->assertTrue($root->is_active);
        $this->assertSame(0, $root->sort_order);
        $this->assertNull($root->permission_key);
        $this->assertNull($root->parent_id);
        $first = $root->children()->create(['name' => 'Dashboard', 'type' => 'link', 'route_name' => 'admin.home']);
        $second = $root->children()->create(['name' => 'Duplicate allowed', 'type' => 'link', 'route_name' => 'admin.home']);
        $first->sort_order = 5;
        $first->save();
        $this->assertSame($root->id, $first->parent->id);
        $this->assertSame([$second->id, $first->id], $root->children->modelKeys());
        $this->assertSame([$root->id], AdminMenu::roots()->active()->ordered()->pluck('id')->all());
    }

    public function test_parent_delete_is_restricted_and_leaf_can_be_deleted(): void
    {
        $root = AdminMenu::create(['name' => 'Group', 'type' => 'group']);
        $child = $root->children()->create(['name' => 'Child', 'type' => 'link', 'route_name' => 'admin.home']);
        try {
            $root->delete();
            $this->fail('Expected RESTRICT to reject parent deletion.');
        } catch (QueryException) {
            $this->assertTrue($root->fresh()->exists);
        }
        $child->delete();
        $root->delete();
        $this->assertSame(0, AdminMenu::count());
    }

    public function test_database_rejects_orphans_self_parent_invalid_types_and_routes(): void
    {
        foreach ([
            ['parent_id' => 999], ['id' => 40, 'parent_id' => 40],
            ['type' => 'external'], ['type' => 'link', 'route_name' => null],
            ['type' => 'link', 'route_name' => ' '], ['route_name' => 'admin.home'],
            ['sort_order' => -1], ['sort_order' => 4294967296],
        ] as $invalid) {
            try {
                DB::table('admin_menus')->insert(array_merge(['name' => 'Invalid', 'type' => 'group'], $invalid));
                $this->fail('Expected integrity constraint failure.');
            } catch (QueryException) {
                $this->assertSame(0, AdminMenu::count());
            }
        }
    }

    public function test_inactive_parent_does_not_mutate_child_and_system_fields_are_not_fillable(): void
    {
        $root = AdminMenu::create(['name' => 'Group', 'type' => 'group']);
        $child = $root->children()->create(['name' => 'Child', 'type' => 'link', 'route_name' => 'admin.home']);
        $root->is_active = false;
        $root->save();
        $this->assertTrue($child->refresh()->is_active);
        foreach (['parent_id', 'sort_order', 'is_active', 'permission_key', 'id'] as $field) {
            $this->assertFalse($root->isFillable($field));
        }
    }

    public function test_migration_can_rollback_and_recreate_its_own_table(): void
    {
        $migration = require database_path('migrations/2026_10_20_000001_create_admin_menus_table.php');
        $migration->down();
        $this->assertFalse(Schema::hasTable('admin_menus'));
        $migration->up();
        $this->assertTrue(Schema::hasTable('admin_menus'));
        $indexes = Schema::getIndexes('admin_menus');
        $this->assertContains('admin_menus_siblings_index', array_column($indexes, 'name'));
    }
}
