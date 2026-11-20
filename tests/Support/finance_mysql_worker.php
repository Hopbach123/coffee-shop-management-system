<?php

use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$database = getenv('CAFE_FINANCE_MYSQL_DATABASE');
if (! is_string($database) || ! preg_match('/\Acoffee_finance_qa_[a-z0-9_]+\z/', $database)) {
    throw new RuntimeException('A dedicated Finance QA database is required.');
}
$connection = config('database.connections.mysql');
$connection['url'] = null;
$connection['database'] = $database;
config(['database.default' => 'finance_mysql', 'database.connections.finance_mysql' => $connection]);
DB::purge('finance_mysql');
$payload = json_decode(file_get_contents($argv[1]), true, 512, JSON_THROW_ON_ERROR);
file_put_contents($argv[2], 'ready');
$deadline = microtime(true) + 15;
while (! file_exists($argv[3])) {
    if (microtime(true) > $deadline) {
        throw new RuntimeException('Finance QA barrier timed out.');
    }
    usleep(10000);
}
try {
    Payment::creating(static fn () => usleep(150000));
    $actor = User::findOrFail($payload['actor']);
    $service = app(PaymentService::class);
    $result = $payload['method'] === 'cash'
        ? $service->confirmCash($payload['order'], $payload['key'], '30.00', $actor)
        : $service->confirmBankTransfer($payload['order'], $payload['key'], $payload['reference'], '25.10', $actor);
    echo json_encode(['status' => 'ok', 'id' => $result->paymentId, 'replay' => $result->replay], JSON_THROW_ON_ERROR);
} catch (ValidationException $exception) {
    echo json_encode(['status' => 'rejected', 'fields' => array_keys($exception->errors())], JSON_THROW_ON_ERROR);
} catch (DomainException) {
    echo json_encode(['status' => 'rejected', 'fields' => ['payment']], JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    echo json_encode(['status' => 'error', 'class' => $exception::class], JSON_THROW_ON_ERROR);
    exit(1);
}
