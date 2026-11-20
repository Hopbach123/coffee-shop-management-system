<?php

use App\Models\Area;
use App\Models\CafeTable;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\TableStatusReadService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ViewErrorBag;

// Synthetic view-only fixtures: no live database access, no schema or business writes.
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'session.driver' => 'array']);
DB::purge();
if (DB::connection()->getDriverName() !== 'sqlite' || DB::connection()->getDatabaseName() !== ':memory:') {
    throw new RuntimeException('Fixture isolation failed.');
}
DB::connection()->beforeExecuting(function ($sql): void {
    if (! preg_match('/\A\s*select\b/i', $sql)) {
        throw new RuntimeException('Fixtures must not write database.');
    }
});
URL::forceRootUrl('http://127.0.0.1:8798');
$user = (new User)->forceFill(['name' => 'UI Fixture Admin', 'role' => 'admin', 'status' => 'active']);
auth('web')->setUser($user);
$app['view']->share('errors', new ViewErrorBag);
$labels = TableStatusReadService::LABELS;
$now = CarbonImmutable::parse('2026-10-20 12:00:00', 'UTC');
$area = (new Area)->forceFill(['name' => 'Sân vườn · '.str_repeat('KhuVựcDài', 8), 'description' => 'Fixture giao diện — không phải dữ liệu thật.', 'status' => 'active']);
$area->id = 1;
$area->created_at = $now;
$area->tables_count = $area->cafe_tables_count = 6;
foreach ($labels as $key => $label) {
    $area->{$key.'_count'} = 1;
}
$tables = collect();
foreach (array_keys($labels) as $i => $status) {
    $table = (new CafeTable)->forceFill(['table_code' => 'B'.($i + 1).'-'.str_repeat('LONGCODE', 6), 'table_name' => 'Bàn gần cửa sổ', 'status' => $status === 'occupied' ? 'occupied' : 'available', 'capacity' => 6]);
    $table->id = $i + 1;
    $table->area_id = 1;
    $table->display_status = $status;
    $table->pending_order_count = $status === 'occupied' ? 1 : 0;
    $table->current_reservation_count = $status === 'reserved' ? 1 : 0;
    $table->future_reservation_count = 2;
    $table->pending_payment_count = 0;
    $table->paid_payment_count = 0;
    $table->setRelation('area', $area);
    $tables->push($table);
}
$table = $tables[1];
$category = new Category(['name' => 'Cà phê']);
$category->id = 1;
$variants = collect();
for ($i = 1; $i <= 6; $i++) {
    $product = (new Product)->forceFill(['name' => 'Cà phê '.str_repeat('TênSảnPhẩmDài', 5), 'status' => 'available']);
    $product->setRelation('category', $category);
    $variant = (new ProductVariant)->forceFill(['name' => 'Lớn', 'price' => '123456.70']);
    $variant->id = $i;
    $variant->setRelation('product', $product);
    $variants->push($variant);
}
$order = (new Order)->forceFill(['order_code' => 'ORDER-'.str_repeat('LONG', 15), 'status' => 'pending', 'subtotal' => '123456.70', 'discount_amount' => '0.00', 'tax_amount' => '0.00', 'total_amount' => '123456.70', 'ordered_at' => $now]);
$order->id = 1;
$item = (new OrderItem)->forceFill(['product_name' => 'Cà phê '.str_repeat('TênMónDài', 8), 'variant_name' => 'Lớn', 'quantity' => 1, 'unit_price' => '123456.70', 'topping_amount' => '0.00', 'subtotal' => '123456.70', 'status' => 'pending', 'note' => str_repeat('GhiChúDài', 40)]);
$item->setRelation('orderItemToppings', collect());
$order->setRelation('orderItems', collect([$item]));
$payment = (new Payment)->forceFill(['payment_code' => 'PAY-REFERENCE', 'status' => 'pending', 'payment_method' => 'cash', 'amount' => '123456.70']);
$paginate = fn ($items) => new LengthAwarePaginator($items, count($items), 15, 1, ['path' => '/fixtures']);
$shared = ['observedAt' => $now, 'statuses' => $labels, 'displayLabels' => $labels, 'filters' => [], 'search' => '', 'status' => ''];
$floorSummary = (object) array_merge(['total' => 6], array_fill_keys(array_keys($labels), 1));
$areaSummary = (object) ['areas_count' => 1, 'tables_count' => 6, 'available_count' => 1, 'occupied_count' => 1, 'reserved_count' => 1, 'maintenance_count' => 1, 'conflict_count' => 1, 'unknown_count' => 1];
$snapshot = ['items' => [['product_name' => $item->product_name, 'variant_name' => 'Lớn', 'quantity' => 1, 'unit_price' => '123456.70', 'subtotal' => '123456.70', 'status' => 'pending', 'note' => $item->note]], 'total_amount' => '123456.70', 'discount_amount' => '0.00', 'tax_amount' => '0.00'];
$fixtures = [
    'areas' => ['admin.areas.index', 'admin.areas.index', ['areas' => $paginate([$area]), 'summary' => $areaSummary]],
    'floor' => ['admin.tables.floor-plan', 'admin.tables.index', ['areas' => collect([$area]), 'tables' => $paginate($tables), 'summary' => $floorSummary]],
    'detail' => ['admin.tables.detail', 'admin.tables.show', ['table' => $table, 'tableId' => $table->id, 'candidates' => collect([$order])]],
    'menu' => ['staff.orders.table-menu', 'staff.table-order.index', ['table' => $table, 'order' => $order, 'variants' => $paginate($variants), 'categories' => collect([$category]), 'snapshot' => $snapshot]],
    'checkout' => ['staff.orders.checkout-summary', 'staff.table-checkout.show', ['table' => $table, 'order' => $order, 'tableId' => $table->id, 'orderId' => 1, 'unavailable' => false, 'payments' => $paginate([$payment])]],
    'estimate' => ['staff.orders.estimate-print', 'staff.table-checkout.print', ['table' => $table, 'order' => $order, 'tableId' => $table->id, 'orderId' => 1]],
    'floor-error' => ['admin.tables.floor-plan', 'admin.tables.index', ['areas' => null, 'tables' => null, 'summary' => null]],
    'floor-empty' => ['admin.tables.floor-plan', 'admin.tables.index', ['areas' => collect(), 'tables' => $paginate([]), 'summary' => (object) array_merge(['total' => 0], array_fill_keys(array_keys($labels), 0))]],
];
$directory = storage_path('app/task98-ui');
if (! is_dir($directory)) {
    mkdir($directory, 0777, true);
}
foreach ($fixtures as $name => [$view, $routeName, $data]) {
    $request = Request::create('http://127.0.0.1:8798/fixtures/'.$name.'.html');
    $request->setRouteResolver(fn () => Route::getRoutes()->getByName($routeName));
    $request->setUserResolver(fn () => $user);
    $request->setLaravelSession($app['session']->driver());
    $app->instance('request', $request);
    file_put_contents($directory.'/'.$name.'.html', view($view, array_merge($shared, $data))->render());
}
echo "Rendered 8 synthetic fixtures; SQLite :memory:, no database writes.\n";
