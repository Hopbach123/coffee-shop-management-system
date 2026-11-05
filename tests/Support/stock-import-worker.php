<?php

use App\Models\Ingredient;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

try {
    $input = json_decode(stream_get_contents(STDIN), true, 16, JSON_THROW_ON_ERROR);
    if (! preg_match('/\Ainventory_import_test_[0-9a-f]{16}\z/', $input['connection']['database'] ?? '')) {
        throw new RuntimeException('Unsafe test database.');
    }
    config(['database.default' => 'stock_worker', 'database.connections.stock_worker' => $input['connection']]);
    DB::purge('stock_worker');
    $ingredient = Ingredient::findOrFail($input['ingredient']);
    $actor = User::findOrFail($input['actor']);
    echo "READY\n";
    flush();
    $movement = app(InventoryService::class)->recordImport(
        $ingredient, $actor, $input['quantity'], 'concurrent fixture', $input['key'], 'kg'
    );
    echo json_encode(['id' => $movement->id], JSON_THROW_ON_ERROR)."\n";
} catch (Throwable $exception) {
    // Do not expose connection details or SQL parameters from worker processes.
    fwrite(STDERR, get_class($exception)."\n");
    exit(1);
}
