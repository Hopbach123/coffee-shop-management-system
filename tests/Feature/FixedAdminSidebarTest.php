<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FixedAdminSidebarTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
        $admin = User::forceCreate(['name' => 'Admin', 'email' => 'fixed-menu@example.test', 'password' => 'test-only', 'role' => 'admin', 'status' => 'active']);
        $this->actingAs($admin, 'web');
        $this->withoutVite();
    }

    public function test_complete_fixed_navigation_renders_without_a_menu_table_or_database_queries(): void
    {
        $this->assertFalse(Schema::hasTable('admin_menus'));
        DB::enableQueryLog();
        $html = Blade::render('<x-internal.fixed-admin-sidebar />');
        $this->assertCount(0, DB::getQueryLog());
        DB::disableQueryLog();
        $count = 0;
        foreach (config('admin_navigation') as $group) {
            $this->assertStringContainsString(e($group['label']), $html);
            foreach ($group['items'] as $item) {
                $count++;
                $route = Route::getRoutes()->getByName($item['route']);
                $this->assertNotNull($route);
                $this->assertContains('GET', $route->methods());
                $this->assertSame([], $route->parameterNames());
                $this->assertContains('auth:web', $route->gatherMiddleware());
                $this->assertContains('role:admin', $route->gatherMiddleware());
                $this->assertStringContainsString(route($item['route']), $html);
                $this->assertStringContainsString(e($item['label']), $html);
            }
        }
        $this->assertSame(11, $count);
        $this->assertStringNotContainsString(route('admin.menus.index'), $html);
        $this->assertStringNotContainsString(route('staff.home'), $html);
        $this->assertStringContainsString('Chọn nguyên liệu để nhập kho', $html);
    }

    public function test_recipe_and_inventory_contexts_activate_exactly_one_appropriate_parent(): void
    {
        foreach ([
            'admin.home' => 'admin.home',
            'admin.product-recipes.index' => 'admin.product-variants.index',
            'admin.product-variants.recipe' => 'admin.product-variants.index',
            'admin.product-variants.recipes' => 'admin.product-variants.index',
            'admin.ingredients.import.create' => 'admin.ingredients.index',
            'admin.ingredients.adjustment.create' => 'admin.ingredients.index',
            'admin.ingredients.history' => 'admin.ingredients.index',
            'admin.products.edit' => 'admin.products.index',
        ] as $current => $parent) {
            $route = Route::getRoutes()->getByName($current);
            request()->setRouteResolver(fn () => $route);
            $html = Blade::render('<x-internal.fixed-admin-sidebar />');
            preg_match_all('/<a href="([^"]+)"[^>]*aria-current="page"/', $html, $matches);
            $this->assertSame([route($parent)], $matches[1], $current);
        }
    }

    public function test_staff_guest_and_inactive_accounts_do_not_render_admin_links(): void
    {
        $user = auth('web')->user();
        $user->forceFill(['role' => 'staff'])->save();
        $this->assertSame('', trim(Blade::render('<x-internal.fixed-admin-sidebar />')));
        $user->forceFill(['role' => 'admin', 'status' => 'inactive'])->save();
        $this->assertSame('', trim(Blade::render('<x-internal.fixed-admin-sidebar />')));
        auth('web')->logout();
        $this->assertSame('', trim(Blade::render('<x-internal.fixed-admin-sidebar />')));
    }

    public function test_actual_admin_layout_uses_fixed_navigation_while_staff_keeps_sales_workspace(): void
    {
        $html = $this->get('/admin/users/create')->assertOk()->getContent();
        $this->assertStringContainsString('data-fixed-admin-sidebar', $html);
        $this->assertStringNotContainsString('dynamic-admin-sidebar', $html);
        $this->assertStringContainsString('Mở POS', $html);
        $this->get('/staff')->assertOk()->assertDontSee('data-fixed-admin-sidebar', false)
            ->assertSee('Về Quản trị')->assertSee('POS / Bán tại quầy');
    }
}
