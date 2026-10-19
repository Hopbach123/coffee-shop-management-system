<?php

// Test helper only: credentials arrive through stdin, never command-line arguments.
use App\Models\Reservation;
use App\Models\User;
use App\Services\ReservationApprovalService;
use App\Services\ReservationCancellationService;
use App\Services\ReservationCreationService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$app->instance('env', 'testing');
$input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
if (! preg_match('/^coffee_reservation_test_[a-z0-9_]+$/', $input['connection']['database'] ?? '')) {
    exit(2);
}
config(['database.default' => 'reservation_concurrency', 'database.connections.reservation_concurrency' => $input['connection']]);
DB::purge('reservation_concurrency');
$actor = User::findOrFail($input['actor']);
$attemptedLock = false;
DB::connection()->beforeExecuting(function (string $query) use (&$attemptedLock): void {
    if (! $attemptedLock && str_contains(strtolower($query), 'for update')) {
        $attemptedLock = true;
        echo "locking\n";
        flush();
    }
});
echo "ready\n";
flush();
try {
    if (isset($input['payload']['_cancel'])) {
        app(ReservationCancellationService::class)->cancel(Reservation::findOrFail($input['payload']['_cancel']), 'pending');
        echo "cancelled\n";
    } elseif (isset($input['payload']['_approve'])) {
        app(ReservationApprovalService::class)->approve(Reservation::findOrFail($input['payload']['_approve']));
        echo "approved\n";
    } else {
        app(ReservationCreationService::class)->create($input['payload'], $actor);
        echo "created\n";
    }
} catch (ValidationException $exception) {
    echo array_key_exists('table_id', $exception->errors()) ? "conflict\n" : "invalid\n";
} catch (Throwable $exception) {
    // No SQL, credentials or contact data in subprocess output.
    echo "error\n";
    exit(1);
}
