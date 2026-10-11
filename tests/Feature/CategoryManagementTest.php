<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\CategoryController;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\View as BladeView;
use Mockery;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Refuse to prepare fixtures against any persistent database.
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());

        foreach ([
            '0001_01_01_000000_create_users_table.php',
            '2026_08_14_000003_create_categories_table.php',
            '2026_08_14_000007_create_products_table.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
    }

    private function user(string $role = 'admin'): User
    {
        return User::forceCreate([
            'name' => 'Category test user',
            'email' => $role.'@example.test',
            'password' => 'test-password',
            'role' => $role,
            'status' => 'active',
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'name' => 'Coffee',
            'description' => 'Coffee drinks',
            'display_order' => 2,
            'status' => 'active',
        ], $overrides);
    }

    private function category(array $overrides = []): Category
    {
        return Category::forceCreate($this->payload($overrides));
    }

    private function categoryEndpoints(Category $category): array
    {
        return [
            ['GET', '/admin/categories'],
            ['GET', '/admin/categories/create'],
            ['POST', '/admin/categories'],
            ['GET', '/admin/categories/'.$category->id.'/edit'],
            ['PUT', '/admin/categories/'.$category->id],
            ['PATCH', '/admin/categories/'.$category->id],
            ['PATCH', '/admin/categories/'.$category->id.'/toggle-status'],
            ['DELETE', '/admin/categories/'.$category->id],
        ];
    }

    public function test_all_category_routes_inherit_existing_admin_protection(): void
    {
        foreach (['index', 'create', 'store', 'edit', 'update', 'destroy', 'toggle-status'] as $action) {
            $route = app('router')->getRoutes()->getByName('admin.categories.'.$action);
            $this->assertNotNull($route);
            $this->assertContains('auth:web', $route->gatherMiddleware());
            $this->assertContains('role:admin', $route->gatherMiddleware());
        }
    }

    public function test_guest_cannot_access_any_category_endpoint(): void
    {
        $category = $this->category();

        foreach ($this->categoryEndpoints($category) as [$method, $uri]) {
            $this->call($method, $uri, $this->payload())->assertRedirect('/login');
        }

        $this->assertDatabaseCount('categories', 1);
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'status' => 'active', 'deleted_at' => null]);
    }

    public function test_staff_cannot_read_or_mutate_categories(): void
    {
        $category = $this->category();
        $staff = $this->user('staff');
        $this->actingAs($staff, 'web');

        foreach ($this->categoryEndpoints($category) as [$method, $uri]) {
            $this->call($method, $uri, $this->payload(['name' => 'Unauthorized']))->assertForbidden();
        }

        $this->assertAuthenticatedAs($staff, 'web');
        $this->assertDatabaseCount('categories', 1);
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Coffee', 'status' => 'active', 'deleted_at' => null]);
    }

    public function test_admin_can_create_category_and_duplicate_names_are_allowed(): void
    {
        $this->actingAs($this->user(), 'web');

        foreach (['active', 'inactive'] as $status) {
            $this->post('/admin/categories', $this->payload(['status' => $status]))
                ->assertRedirect(route('admin.categories.index'))->assertSessionHas('success');
            $this->assertDatabaseHas('categories', ['name' => 'Coffee', 'status' => $status]);
        }

        $this->assertDatabaseCount('categories', 2);
    }

    public function test_omitted_display_order_uses_database_default_and_description_is_optional(): void
    {
        $this->actingAs($this->user(), 'web')
            ->post('/admin/categories', ['name' => 'Tea', 'status' => 'active'])
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', ['name' => 'Tea', 'display_order' => 0, 'description' => null, 'image' => null]);
    }

    public function test_create_and_update_reject_invalid_inputs_and_preserve_old_input(): void
    {
        $category = $this->category();
        $this->actingAs($this->user(), 'web');

        foreach ([
            ['name', ''],
            ['name', str_repeat('a', 256)],
            ['name', ['invalid']],
            ['description', ['invalid']],
            ['display_order', 'invalid'],
            ['display_order', 1.5],
            ['display_order', null],
            ['display_order', 2147483648],
            ['display_order', -2147483649],
            ['status', 'available'],
            ['status', ''],
            ['status', ['active']],
        ] as [$field, $value]) {
            $data = $this->payload([$field => $value]);

            $this->from('/admin/categories/create')->post('/admin/categories', $data)
                ->assertRedirect('/admin/categories/create')->assertSessionHasErrors($field)
                ->assertSessionHas('_old_input.description', $data['description']);

            $this->from('/admin/categories/'.$category->id.'/edit')
                ->put('/admin/categories/'.$category->id, $data)
                ->assertRedirect('/admin/categories/'.$category->id.'/edit')->assertSessionHasErrors($field);
        }

        $this->assertDatabaseCount('categories', 1);
        $this->assertSame('Coffee', $category->fresh()->name);
    }

    public function test_admin_can_update_only_approved_fields_and_preserve_image(): void
    {
        $category = $this->category(['image' => 'categories/existing.jpg']);
        $createdAt = $category->created_at;

        $this->actingAs($this->user(), 'web')->put('/admin/categories/'.$category->id, $this->payload([
            'name' => str_repeat('a', 255),
            'description' => null,
            'display_order' => -1,
            'status' => 'inactive',
            'image' => 'unapproved.jpg',
            'id' => 999999,
            'created_at' => '2000-01-01 00:00:00',
            'deleted_at' => '2000-01-01 00:00:00',
        ]))->assertRedirect(route('admin.categories.index'))->assertSessionHas('success')->assertSessionHasNoErrors();

        $category->refresh();
        $this->assertSame(str_repeat('a', 255), $category->name);
        $this->assertNull($category->description);
        $this->assertSame(-1, $category->display_order);
        $this->assertSame('inactive', $category->status);
        $this->assertSame('categories/existing.jpg', $category->image);
        $this->assertTrue($createdAt->equalTo($category->created_at));
        $this->assertNull($category->deleted_at);
        $this->assertNotSame(999999, $category->id);
    }

    public function test_create_ignores_system_fields_and_image(): void
    {
        $this->actingAs($this->user(), 'web')->post('/admin/categories', $this->payload([
            'id' => 999999,
            'image' => 'unapproved.jpg',
            'deleted_at' => '2000-01-01 00:00:00',
        ]))->assertRedirect(route('admin.categories.index'));

        $category = Category::firstOrFail();
        $this->assertNotSame(999999, $category->id);
        $this->assertNull($category->image);
        $this->assertNull($category->deleted_at);
    }

    public function test_admin_can_toggle_status_in_both_directions_without_deleting_category(): void
    {
        $category = $this->category();
        $this->actingAs($this->user(), 'web');

        foreach (['inactive', 'active'] as $expected) {
            $this->patch('/admin/categories/'.$category->id.'/toggle-status')
                ->assertRedirect(route('admin.categories.index'))->assertSessionHas('success');
            $this->assertDatabaseHas('categories', ['id' => $category->id, 'status' => $expected, 'deleted_at' => null]);
        }
    }

    public function test_soft_delete_retains_category_row_and_related_products(): void
    {
        $category = $this->category();
        $product = Product::create(['name' => 'Latte', 'slug' => 'latte', 'category_id' => $category->id]);

        $this->actingAs($this->user(), 'web')->delete('/admin/categories/'.$category->id)
            ->assertRedirect(route('admin.categories.index'))->assertSessionHas('success');

        $this->assertSoftDeleted('categories', ['id' => $category->id]);
        $this->assertNull(Category::find($category->id));
        $this->assertNotNull(Category::withTrashed()->find($category->id));
        $this->assertDatabaseHas('products', ['id' => $product->id, 'category_id' => $category->id, 'deleted_at' => null]);
        $this->assertTrue($product->fresh()->category->trashed());
    }

    public function test_missing_and_soft_deleted_categories_cannot_be_edited_or_mutated(): void
    {
        $category = $this->category();
        $category->delete();
        $this->actingAs($this->user(), 'web');

        foreach ([$category->id, 999999] as $id) {
            $this->get('/admin/categories/'.$id.'/edit')->assertNotFound();
            $this->put('/admin/categories/'.$id, $this->payload())->assertNotFound();
            $this->patch('/admin/categories/'.$id.'/toggle-status')->assertNotFound();
            $this->delete('/admin/categories/'.$id)->assertNotFound();
        }
    }

    public function test_all_mutations_require_csrf_and_toggle_does_not_accept_get(): void
    {
        $category = $this->category();
        $this->actingAs($this->user(), 'web');
        $this->app->instance('env', 'local');

        $this->post('/admin/categories', $this->payload())->assertStatus(419);
        $this->put('/admin/categories/'.$category->id, $this->payload())->assertStatus(419);
        $this->patch('/admin/categories/'.$category->id.'/toggle-status')->assertStatus(419);
        $this->delete('/admin/categories/'.$category->id)->assertStatus(419);
        $this->get('/admin/categories/'.$category->id.'/toggle-status')->assertStatus(405);
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'status' => 'active', 'deleted_at' => null]);
    }

    public function test_list_query_sorts_by_display_order_then_id_and_excludes_deleted_categories(): void
    {
        $last = $this->category(['name' => 'Last', 'display_order' => 10]);
        $first = $this->category(['name' => 'First', 'display_order' => -1, 'status' => 'inactive']);
        $second = $this->category(['name' => 'Second', 'display_order' => -1]);
        $this->category(['name' => 'Deleted', 'display_order' => -2])->delete();

        $this->inspectListData([], function (array $data) use ($first, $second, $last): void {
            $this->assertSame([$first->id, $second->id, $last->id], $data['categories']->pluck('id')->all());
            $this->assertSame(3, $data['categories']->total());
        });
    }

    public function test_name_search_and_pagination_preserve_search_parameter(): void
    {
        for ($index = 1; $index <= 16; $index++) {
            $this->category(['name' => 'Coffee '.$index]);
        }
        $this->category(['name' => 'Tea', 'description' => 'Coffee only in description']);
        $this->category(['name' => 'Coffee deleted'])->delete();

        $this->inspectListData(['search' => 'Coffee', 'page' => 2], function (array $data): void {
            $categories = $data['categories'];
            $this->assertSame(16, $categories->total());
            $this->assertSame(2, $categories->currentPage());
            $this->assertCount(1, $categories);
            $this->assertSame('Coffee 16', $categories->first()->name);
            $this->assertStringContainsString('search=Coffee', $categories->previousPageUrl());
            $this->assertSame('Coffee', $data['search']);
        });
    }

    public function test_admin_can_render_category_pages_with_existing_internal_layout(): void
    {
        $this->withoutVite();
        $category = $this->category(['name' => 'Coffee']);
        $this->actingAs($this->user(), 'web');

        $this->get('/admin/categories')->assertOk()->assertViewIs('admin.categories.index')
            ->assertSee('Coffee')->assertSee('Danh mục')->assertSee('Khu vực Admin');
        $this->get('/admin/categories/create')->assertOk()->assertViewIs('admin.categories.create')
            ->assertSee('name="name"', false)->assertSee('name="status"', false);
        $this->get('/admin/categories/'.$category->id.'/edit')->assertOk()->assertViewIs('admin.categories.edit')
            ->assertSee('value="Coffee"', false)->assertSee('name="_method" value="PUT"', false);
    }

    public function test_list_renders_empty_states_search_actions_and_pagination_links(): void
    {
        $this->withoutVite();
        $this->actingAs($this->user(), 'web');

        $this->get('/admin/categories')->assertOk()->assertSee('Chưa có danh mục');
        $this->get('/admin/categories?search=Missing')->assertOk()->assertSee('Không tìm thấy danh mục')
            ->assertSee('value="Missing"', false);

        for ($index = 1; $index <= 16; $index++) {
            $this->category(['name' => 'Coffee '.$index]);
        }

        $this->get('/admin/categories?search=Coffee')->assertOk()
            ->assertSee('search=Coffee&amp;page=2', false)
            ->assertSee('name="_method" value="PATCH"', false)
            ->assertSee('name="_method" value="DELETE"', false)
            ->assertSee('Bạn có chắc muốn xóa danh mục này?', false);
    }

    public function test_form_renders_validation_feedback_and_old_input(): void
    {
        $this->withoutVite();
        $this->actingAs($this->user(), 'web');

        $this->from('/admin/categories/create')->post('/admin/categories', $this->payload([
            'name' => '',
            'description' => 'Mô tả đã nhập',
        ]))->assertRedirect('/admin/categories/create')->assertSessionHasErrors('name');

        $errors = new ViewErrorBag;
        $errors->put('default', new MessageBag(['name' => ['Vui lòng nhập tên danh mục.']]));

        $this->get('/admin/categories/create')->assertOk()->assertSee('Mô tả đã nhập');

        $html = view('admin.categories.create', ['errors' => $errors])->render();
        $this->assertStringContainsString('name-error', $html);
        $this->assertStringContainsString('aria-invalid="true"', $html);
    }

    /** Inspect query data directly for sort and search edge cases. */
    private function inspectListData(array $query, callable $assertions): void
    {
        $request = Request::create('/admin/categories', 'GET', $query);
        $this->app->instance('request', $request);
        Paginator::currentPageResolver(fn () => (int) ($query['page'] ?? 1));
        Paginator::queryStringResolver(fn () => $query);

        View::shouldReceive('make')->once()
            ->withArgs(function (string $name, array $data) use ($assertions): bool {
                $this->assertSame('admin.categories.index', $name);
                $assertions($data);

                return true;
            })
            ->andReturn(Mockery::mock(BladeView::class));

        app(CategoryController::class)->index($request);
    }
}
