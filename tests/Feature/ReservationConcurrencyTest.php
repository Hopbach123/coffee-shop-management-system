<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\CafeTable;
use App\Models\Reservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReservationConcurrencyTest extends TestCase
{
    private array $created = [];

    protected function setUp(): void
    {
        parent::setUp();
        $database = getenv('RESERVATION_TEST_MYSQL_DATABASE');
        if (! $database) {
            $this->markTestSkipped('Requires an EMPTY dedicated MySQL database named coffee_reservation_test_*, configured via RESERVATION_TEST_MYSQL_DATABASE.');
        }
        $this->assertMatchesRegularExpression('/^coffee_reservation_test_[a-z0-9_]+$/', $database);
        $connection = config('database.connections.mysql');
        $connection['url'] = null;
        $connection['database'] = $database;
        $connection['engine'] = 'InnoDB';
        foreach (['host', 'port', 'username', 'password'] as $key) {
            $value = getenv('RESERVATION_TEST_MYSQL_'.strtoupper($key));
            if ($value !== false) {
                $connection[$key] = $value;
            }
        }
        config(['database.default' => 'reservation_concurrency', 'database.connections.reservation_concurrency' => $connection]);
        DB::purge('reservation_concurrency');
        $this->assertSame('mysql', DB::connection()->getDriverName());
        $this->assertSame([], Schema::getTableListing(), 'Refusing to touch a non-empty test schema.');
        foreach ([
            ['0001_01_01_000000_create_users_table.php', ['users', 'password_reset_tokens', 'sessions']],
            ['2026_08_14_000001_create_customers_table.php', ['customers']],
            ['2026_08_14_000002_create_areas_table.php', ['areas']],
            ['2026_08_14_000006_create_cafe_tables_table.php', ['cafe_tables']],
            ['2026_08_16_000015_create_reservations_table.php', ['reservations']],
            ['2026_08_16_000016_create_orders_table.php', ['orders']],
        ] as [$migration, $tables]) {
            $this->created = array_merge($this->created, $tables);
            (require database_path('migrations/'.$migration))->up();
        }
    }

    protected function tearDown(): void
    {
        try {
            foreach (array_reverse($this->created) as $table) {
                Schema::dropIfExists($table);
            }
            DB::disconnect('reservation_concurrency');
        } finally {
            parent::tearDown();
        }
    }

    public function test_two_processes_recheck_after_waiting_on_table_lock(): void
    {
        $actor = User::forceCreate(['name' => 'Test Admin', 'email' => 'concurrency@example.test', 'password' => 'test-only-password', 'role' => 'admin', 'status' => 'active']);
        $area = Area::create(['name' => 'Test floor']);
        $table = CafeTable::create(['area_id' => $area->id, 'table_code' => 'T1', 'capacity' => 4]);
        $other = CafeTable::create(['area_id' => $area->id, 'table_code' => 'T2', 'capacity' => 4]);
        $day = now()->addDays(2)->format('Y-m-d');
        foreach (['identical', 'overlap', 'different_tables', 'approval_same', 'approval_conflict', 'create_vs_approve', 'approval_different_tables', 'cancel_same', 'approve_vs_cancel', 'cancel_vs_create'] as $case) {
            $nextDay = $day;
            $payload = ['table_id' => $table->id, 'customer_name' => 'Test guest', 'customer_phone' => '0900000000', 'guest_count' => 2,
                'reservation_start_at' => $nextDay.'T12:00', 'reservation_end_at' => $nextDay.'T14:00'];
            $second = $payload;
            if ($case === 'overlap') {
                $second['reservation_start_at'] = $nextDay.'T13:00';
                $second['reservation_end_at'] = $nextDay.'T15:00';
            } elseif ($case === 'different_tables') {
                $second['table_id'] = $other->id;
            }
            $expected = $case === 'different_tables' ? ['created', 'created'] : ['conflict', 'created'];
            $expectedCount = $case === 'different_tables' ? 2 : 1;
            if (in_array($case, ['approval_same', 'approval_conflict', 'create_vs_approve', 'approval_different_tables'], true)) {
                $firstReservation = Reservation::create($payload);
                $payload['_approve'] = $firstReservation->id;
                $expected = ['approved', 'invalid'];
                if ($case === 'approval_same') {
                    $second['_approve'] = $firstReservation->id;
                } elseif ($case === 'create_vs_approve') {
                    $expected = ['approved', 'conflict'];
                } else {
                    if ($case === 'approval_different_tables') {
                        $second['table_id'] = $other->id;
                    }
                    $secondReservation = Reservation::create($second);
                    $second['_approve'] = $secondReservation->id;
                    $expectedCount = 2;
                    $expected = $case === 'approval_conflict' ? ['conflict', 'conflict'] : ['approved', 'approved'];
                }
            }
            if (in_array($case, ['cancel_same', 'approve_vs_cancel', 'cancel_vs_create'], true)) {
                $pending = Reservation::create($payload);
                $payload['_cancel'] = $pending->id;
                if ($case === 'cancel_same') {
                    $second['_cancel'] = $pending->id;
                } elseif ($case === 'approve_vs_cancel') {
                    $second['_approve'] = $pending->id;
                }
            }
            $workers = [];
            DB::beginTransaction();
            CafeTable::whereKey($table->id)->lockForUpdate()->firstOrFail();
            try {
                foreach ([$payload, $second] as $input) {
                    $pipes = [];
                    $process = proc_open([PHP_BINARY, base_path('tests/Support/reservation-concurrency-worker.php')], [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes, base_path());
                    $this->assertIsResource($process);
                    fwrite($pipes[0], json_encode(['connection' => config('database.connections.reservation_concurrency'), 'actor' => $actor->id, 'payload' => $input], JSON_THROW_ON_ERROR));
                    fclose($pipes[0]);
                    stream_set_blocking($pipes[1], false);
                    stream_set_blocking($pipes[2], false);
                    $workers[] = ['process' => $process, 'pipes' => $pipes, 'output' => ''];
                }
                $deadline = microtime(true) + 15;
                do {
                    foreach ($workers as &$worker) {
                        $worker['output'] .= stream_get_contents($worker['pipes'][1]);
                    }
                    unset($worker);
                    $ready = count(array_filter($workers, fn ($worker) => str_contains($worker['output'], 'locking')));
                    if ($ready === 2) {
                        break;
                    }
                    usleep(20000);
                } while (microtime(true) < $deadline);
                $this->assertSame(2, $ready, 'Workers did not both attempt a locking query.');
                DB::commit();
                $results = [];
                foreach ($workers as &$worker) {
                    $deadline = microtime(true) + 15;
                    do {
                        $worker['output'] .= stream_get_contents($worker['pipes'][1]);
                        if (preg_match('/\b(created|approved|cancelled|conflict|invalid|error)\b/', $worker['output'], $match)) {
                            $results[] = $match[1];
                            break;
                        }
                        usleep(20000);
                    } while (microtime(true) < $deadline);
                }
                unset($worker);
                sort($results);
                if ($case === 'cancel_same') {
                    $expected = ['cancelled', 'invalid'];
                } elseif ($case === 'approve_vs_cancel') {
                    $this->assertContains($results, [['approved', 'invalid'], ['cancelled', 'invalid']]);
                    $expected = $results;
                } elseif ($case === 'cancel_vs_create') {
                    $this->assertContains($results, [['cancelled', 'created'], ['cancelled', 'conflict']]);
                    $expected = $results;
                    $expectedCount = in_array('created', $results, true) ? 2 : 1;
                }
                $this->assertSame($expected, $results, $case);
                $this->assertSame($expectedCount, DB::table('reservations')->whereDate('reservation_start_at', $nextDay)->count());
                $this->assertSame(count(array_filter($expected, fn ($result) => $result === 'approved')), DB::table('reservations')->whereDate('reservation_start_at', $nextDay)->where('status', 'confirmed')->count());
                $this->assertSame(count(array_filter($expected, fn ($result) => $result === 'cancelled')), DB::table('reservations')->whereDate('reservation_start_at', $nextDay)->where('status', 'cancelled')->count());
            } finally {
                if (DB::transactionLevel()) {
                    DB::rollBack();
                }
                foreach ($workers as $worker) {
                    if (proc_get_status($worker['process'])['running']) {
                        proc_terminate($worker['process']);
                    }
                    fclose($worker['pipes'][1]);
                    fclose($worker['pipes'][2]);
                    proc_close($worker['process']);
                }
            }
            $day = CarbonImmutable::parse($day)->addDay()->format('Y-m-d');
        }
    }
}
