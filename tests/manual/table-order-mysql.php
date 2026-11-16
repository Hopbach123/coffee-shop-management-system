<?php

use App\Models\Area;
use App\Models\CafeTable;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\OrderService;
use App\Services\TableOrderContext;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;

// Standalone integration probe: creates and removes only its own fresh test schema.
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (! $app->environment('local') || DB::connection()->getDriverName() !== 'mysql') {
    throw new RuntimeException('Probe requires local MySQL.');
}
$original = DB::getDefaultConnection();
$source = DB::connection();
$pdo = $source->getPdo();
$schemaName = 'coffee_task95_probe_'.bin2hex(random_bytes(6));
if (! preg_match('/\Acoffee_task95_probe_[a-f0-9]{12}\z/', $schemaName)) {
    throw new RuntimeException('Unsafe schema name.');
}
$exists = $pdo->prepare('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?');
$exists->execute([$schemaName]);
if ((int) $exists->fetchColumn() !== 0) {
    throw new RuntimeException('Schema must not exist.');
}
$created = false;
$failed = false;
try {
    $pdo->exec('CREATE DATABASE `'.$schemaName.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $created = true;
    $settings = $source->getConfig();
    $settings['database'] = $schemaName;
    $settings['url'] = null;
    $settings['name'] = 'task95_probe';
    config(['database.connections.task95_probe' => $settings]);
    DB::setDefaultConnection('task95_probe');
    $app->forgetInstance('db.schema');
    Schema::clearResolvedInstance('db.schema');
    if (DB::selectOne('SELECT DATABASE() AS selected_schema')->selected_schema !== $schemaName || Schema::getTableListing($schemaName) !== []) {
        throw new RuntimeException('Test database isolation failed.');
    }
    // Connection::getName is propagated into Eloquent instances after hydration/save.
    // Never inherit the source connection's name into the isolated connection.
    $source->beforeExecuting(fn () => throw new RuntimeException('Original connection is read/write blocked during probe.'));
    DB::connection()->beforeExecuting(function () use ($schemaName): void {
        if (DB::connection()->getName() !== 'task95_probe'
            || DB::connection()->getPdo()->query('SELECT DATABASE()')->fetchColumn() !== $schemaName) {
            throw new RuntimeException('Query escaped the isolated test schema.');
        }
    });
    foreach ([User::class, Area::class, CafeTable::class, Category::class, Product::class, ProductVariant::class, Order::class] as $model) {
        if ((new $model)->getConnection()->getPdo()->query('SELECT DATABASE()')->fetchColumn() !== $schemaName) {
            throw new RuntimeException('Model connection isolation failed.');
        }
    }
    foreach (['0001_01_01_000000_create_users_table.php', '2026_08_14_000001_create_customers_table.php', '2026_08_14_000002_create_areas_table.php', '2026_08_14_000003_create_categories_table.php', '2026_08_14_000006_create_cafe_tables_table.php', '2026_08_14_000007_create_products_table.php', '2026_08_16_000014_create_product_variants_table.php', '2026_08_16_000015_create_reservations_table.php', '2026_08_16_000016_create_orders_table.php', '2026_08_16_000017_create_order_items_table.php'] as $migration) {
        (require database_path('migrations/'.$migration))->up();
    }
    $actor = User::forceCreate(['name' => 'Test Admin', 'email' => 'probe@example.test', 'password' => 'test-only-password', 'role' => 'admin', 'status' => 'active']);
    $area = Area::create(['name' => 'Test area']);
    $table = CafeTable::create(['area_id' => $area->id, 'table_code' => 'PROBE']);
    $category = Category::create(['name' => 'Test category']);
    $product = Product::forceCreate(['name' => 'Test product', 'slug' => 'probe', 'category_id' => $category->id, 'status' => 'available']);
    $variant = ProductVariant::forceCreate(['product_id' => $product->id, 'name' => 'Large', 'price' => '12.35', 'status' => 'available']);
    $order = app(OrderService::class)->create(['order_type' => 'dine_in', 'table_id' => $table->id], $actor);
    $context = app(TableOrderContext::class);
    $result = $context->save($table->id, $order->id, ['variant_id' => $variant->id, 'quantity' => 2, 'note' => 'Test note'], $actor);
    if ($result['snapshot']['total_amount'] !== '24.70' || $order->orderItems()->count() !== 1 || $order->fresh()->total_amount !== '24.70') {
        throw new RuntimeException('Persistence or total mismatch.');
    }
    $table->forceFill(['status' => 'maintenance'])->save();
    try {
        $context->save($table->id, $order->id, ['variant_id' => $variant->id, 'quantity' => 1], $actor);
        throw new RuntimeException('Maintenance should reject save.');
    } catch (HttpException $exception) {
        if ($exception->getStatusCode() !== 409) {
            throw $exception;
        }
    }
    if ($order->orderItems()->count() !== 1) {
        throw new RuntimeException('Rejected save wrote an item.');
    }
    echo "PASS isolated MySQL: real migrations, Admin create, item persistence, 24.70 server total, maintenance rejection.\n";
} catch (Throwable $exception) {
    $failed = true;
    echo 'FAIL isolated MySQL probe: '.get_class($exception).PHP_EOL;
    if ($exception instanceof DomainException) {
        echo $exception->getMessage().PHP_EOL;
    }
} finally {
    DB::disconnect('task95_probe');
    DB::setDefaultConnection($original);
    $app->forgetInstance('db.schema');
    Schema::clearResolvedInstance('db.schema');
    if ($created && preg_match('/\Acoffee_task95_probe_[a-f0-9]{12}\z/', $schemaName)) {
        $pdo->exec('DROP DATABASE `'.$schemaName.'`');
        echo "Removed only the schema created by this probe.\n";
    }
}
exit($failed ? 1 : 0);
