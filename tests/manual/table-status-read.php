<?php

use App\Services\TableStatusReadService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! $app->environment('local') || DB::connection()->getDriverName() !== 'mysql') {
    throw new RuntimeException('Requires local MySQL; performs SELECT only.');
}
DB::connection()->beforeExecuting(function ($sql): void {
    if (! preg_match('/\A\s*select\b/i', $sql)) {
        throw new RuntimeException('Non-SELECT query rejected.');
    }
});
$reader = app(TableStatusReadService::class);
$now = CarbonImmutable::now(config('app.timezone'));
$query = $reader->query($now)->forFloorPlan([]);
$summary = (clone $query)->select([])->selectRaw('COUNT(*) AS total')->first();
$tables = $query->get();
$areas = $reader->areas('', '', $now)->get();
if ((int) $summary->total !== $tables->count() || (int) $areas->sum('tables_count') !== $tables->count()) {
    throw new RuntimeException('Floor/area total mismatch.');
}
foreach ($areas as $area) {
    $sum = 0;
    foreach (array_keys(TableStatusReadService::LABELS) as $status) {
        $count = (int) $area->getAttribute($status.'_count');
        $sum += $count;
        if ($count !== $tables->where('area_id', $area->id)->where('display_status', $status)->count()) {
            throw new RuntimeException('Area bucket mismatch.');
        }
    }
    if ($sum !== (int) $area->tables_count) {
        throw new RuntimeException('Buckets do not reconcile.');
    }
}
echo 'PASS: SELECT-only MySQL read; '.$tables->count().' tables, '.$areas->count()." areas; all display buckets reconcile.\n";
