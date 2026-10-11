<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class ProductImageUploadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());

        foreach ([
            '0001_01_01_000000_create_users_table.php',
            '2026_08_14_000003_create_categories_table.php',
            '2026_08_14_000007_create_products_table.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }

        Storage::fake('public');
        $this->withoutVite();
    }

    private function user(string $role = 'admin'): User
    {
        return User::forceCreate([
            'name' => 'Image test user', 'email' => $role.'@example.test',
            'password' => 'test-password', 'role' => $role, 'status' => 'active',
        ]);
    }

    private function category(): Category
    {
        return Category::forceCreate(['name' => 'Coffee', 'status' => 'active']);
    }

    private function payload(Category $category, array $changes = []): array
    {
        return array_replace([
            'category_id' => $category->id, 'name' => 'Latte',
            'description' => 'Milk coffee', 'status' => 'available', 'is_featured' => '0',
        ], $changes);
    }

    private function product(Category $category, ?string $image = null): Product
    {
        return Product::forceCreate([
            'category_id' => $category->id, 'name' => 'Latte', 'slug' => 'latte',
            'status' => 'available', 'is_featured' => false, 'image' => $image,
        ]);
    }

    public function test_admin_can_create_product_without_image(): void
    {
        $category = $this->category();
        $this->actingAs($this->user(), 'web');

        $this->get(route('admin.products.create'))->assertOk()
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('name="image"', false);
        $this->post(route('admin.products.store'), $this->payload($category))
            ->assertRedirect(route('admin.products.index'))->assertSessionHasNoErrors();

        $this->assertNull(Product::firstOrFail()->image);
        $this->assertCount(0, Storage::disk('public')->allFiles('products'));
    }

    public function test_admin_can_upload_jpg_png_and_webp_with_relative_unique_paths(): void
    {
        $category = $this->category();
        $this->actingAs($this->user(), 'web');

        foreach (['jpg', 'png', 'webp'] as $extension) {
            $this->post(route('admin.products.store'), $this->payload($category, [
                'name' => 'Latte '.$extension,
                'image' => UploadedFile::fake()->image('photo.'.$extension),
            ]))->assertRedirect(route('admin.products.index'))->assertSessionHasNoErrors();
        }

        $paths = Product::query()->pluck('image')->all();
        $this->assertCount(3, array_unique($paths));
        foreach ($paths as $path) {
            $this->assertMatchesRegularExpression('~^products/[a-zA-Z0-9_-]+\.(jpg|png|webp)$~', $path);
            $this->assertStringNotContainsString('D:', $path);
            Storage::disk('public')->assertExists($path);
        }
    }

    public function test_invalid_oversized_and_executable_files_are_rejected(): void
    {
        $category = $this->category();
        $this->actingAs($this->user(), 'web');

        foreach ([
            UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
            UploadedFile::fake()->image('large.jpg')->size(5121),
            UploadedFile::fake()->image('script.php'),
            'products/client-chosen.jpg',
        ] as $file) {
            $this->from(route('admin.products.create'))->post(route('admin.products.store'),
                $this->payload($category, ['image' => $file]))
                ->assertRedirect(route('admin.products.create'))->assertSessionHasErrors('image');
        }

        $this->assertDatabaseCount('products', 0);
        $this->assertCount(0, Storage::disk('public')->allFiles('products'));
    }

    public function test_guests_and_staff_cannot_upload_product_images(): void
    {
        $category = $this->category();
        $this->post(route('admin.products.store'), $this->payload($category, [
            'image' => UploadedFile::fake()->image('guest.jpg'),
        ]))->assertRedirect(route('login'));

        $this->actingAs($this->user('staff'), 'web')
            ->post(route('admin.products.store'), $this->payload($category, [
                'image' => UploadedFile::fake()->image('staff.jpg'),
            ]))->assertForbidden();

        $this->assertDatabaseCount('products', 0);
        $this->assertCount(0, Storage::disk('public')->allFiles('products'));
    }

    public function test_edit_preview_and_update_without_file_preserve_existing_image(): void
    {
        $category = $this->category();
        $path = UploadedFile::fake()->image('old.jpg')->store('products', 'public');
        $product = $this->product($category, $path);
        $this->actingAs($this->user(), 'web');

        $this->get(route('admin.products.edit', $product))->assertOk()
            ->assertSee(Storage::disk('public')->url($path))
            ->assertSee('enctype="multipart/form-data"', false);
        $this->put(route('admin.products.update', $product), $this->payload($category, [
            'name' => 'Renamed Latte',
        ]))->assertRedirect(route('admin.products.index'))->assertSessionHasNoErrors();

        $this->assertSame($path, $product->fresh()->image);
        Storage::disk('public')->assertExists($path);
    }

    public function test_replacing_image_updates_db_then_removes_previous_managed_file(): void
    {
        $category = $this->category();
        $oldPath = UploadedFile::fake()->image('old.jpg')->store('products', 'public');
        $product = $this->product($category, $oldPath);
        $this->actingAs($this->user(), 'web');

        $this->post(route('admin.products.update', $product), $this->payload($category, [
            '_method' => 'PUT', 'image' => UploadedFile::fake()->image('new.png'),
        ]))->assertRedirect(route('admin.products.index'))->assertSessionHasNoErrors();

        $newPath = $product->fresh()->image;
        $this->assertNotSame($oldPath, $newPath);
        Storage::disk('public')->assertExists($newPath);
        Storage::disk('public')->assertMissing($oldPath);
    }

    public function test_failed_replacement_keeps_existing_image_and_legacy_file_is_not_deleted(): void
    {
        $category = $this->category();
        $oldPath = UploadedFile::fake()->image('old.jpg')->store('products', 'public');
        $product = $this->product($category, $oldPath);
        $this->actingAs($this->user(), 'web');

        $this->post(route('admin.products.update', $product), $this->payload($category, [
            '_method' => 'PUT', 'image' => UploadedFile::fake()->create('bad.exe', 10),
        ]))->assertSessionHasErrors('image');
        $this->assertSame($oldPath, $product->fresh()->image);
        Storage::disk('public')->assertExists($oldPath);

        $product->image = 'images/coffeehouse/artisan-espresso.png';
        $product->save();
        $this->post(route('admin.products.update', $product), $this->payload($category, [
            '_method' => 'PUT', 'image' => UploadedFile::fake()->image('replacement.jpg'),
        ]))->assertSessionHasNoErrors();
        $this->assertFileExists(public_path('images/coffeehouse/artisan-espresso.png'));
        Storage::disk('public')->assertExists($product->fresh()->image);
    }

    public function test_soft_delete_keeps_image_for_history(): void
    {
        $category = $this->category();
        $path = UploadedFile::fake()->image('keep.jpg')->store('products', 'public');
        $product = $this->product($category, $path);
        $this->actingAs($this->user(), 'web')->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        $this->assertSoftDeleted('products', ['id' => $product->id]);
        Storage::disk('public')->assertExists($path);
    }

    public function test_failed_create_cleans_up_newly_uploaded_file(): void
    {
        $category = $this->category();
        $this->actingAs($this->user(), 'web')->withoutExceptionHandling();
        Product::saving(fn () => false);

        try {
            $this->post(route('admin.products.store'), $this->payload($category, [
                'image' => UploadedFile::fake()->image('new.jpg'),
            ]));
            $this->fail('A cancelled save must fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Product could not be created.', $exception->getMessage());
        } finally {
            Product::flushEventListeners();
        }

        $this->assertDatabaseCount('products', 0);
        $this->assertCount(0, Storage::disk('public')->allFiles('products'));
    }

    public function test_failed_replace_keeps_old_file_and_cleans_up_new_file(): void
    {
        $category = $this->category();
        $oldPath = UploadedFile::fake()->image('old.jpg')->store('products', 'public');
        $product = $this->product($category, $oldPath);
        $this->actingAs($this->user(), 'web')->withoutExceptionHandling();
        Product::saving(fn () => false);

        try {
            $this->post(route('admin.products.update', $product), $this->payload($category, [
                '_method' => 'PUT', 'image' => UploadedFile::fake()->image('new.png'),
            ]));
            $this->fail('A cancelled save must fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Product could not be updated.', $exception->getMessage());
        } finally {
            Product::flushEventListeners();
        }

        $this->assertSame($oldPath, $product->fresh()->image);
        Storage::disk('public')->assertExists($oldPath);
        $this->assertCount(1, Storage::disk('public')->allFiles('products'));
    }
}
