<?php

use App\Models\CustomerOrderRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\CustomerOrderService;
use App\Services\PaymentService;
use App\Services\PosCheckoutService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$database = getenv('CAFE_WORKFLOW_MYSQL_DATABASE');
if (! is_string($database) || ! preg_match('/^coffee_workflow_qa(?:_[a-z0-9_]+)?$/', $database)) {
    throw new RuntimeException('Dedicated workflow QA database required.');
}
$connection = config('database.connections.mysql');
$connection['url'] = null;
$connection['host'] = '127.0.0.1';
$connection['port'] = getenv('CAFE_WORKFLOW_MYSQL_PORT') ?: '3308';
$connection['database'] = $database;
$connection['username'] = getenv('CAFE_WORKFLOW_MYSQL_USERNAME') ?: 'root';
$connection['password'] = getenv('CAFE_WORKFLOW_MYSQL_PASSWORD') ?: '';
config(['database.default' => 'workflow_mysql', 'database.connections.workflow_mysql' => $connection]);
DB::purge('workflow_mysql');
$payload = json_decode(file_get_contents($argv[1]), true, 512, JSON_THROW_ON_ERROR);
file_put_contents($argv[2], 'ready');
$deadline = microtime(true) + 15;
while (! file_exists($argv[3])) {
    if (microtime(true) > $deadline) {
        throw new RuntimeException('QA barrier timed out.');
    }
    usleep(10000);
}
try {
    Order::creating(static fn () => usleep(200000));
    Payment::creating(static fn () => usleep(200000));
    $actor = isset($payload['actor']) ? User::findOrFail($payload['actor']) : null;
    $result = match ($payload['mode']) {
        'pos' => app(PosCheckoutService::class)->checkout($payload['attributes'], $payload['draft'], $payload['key'], $payload['quote_hash'], $actor),
        'submit' => app(CustomerOrderService::class)->submit($payload['attributes'], $payload['draft'], $payload['key'], $payload['token'], $payload['quote_hash']),
        'accept' => app(CustomerOrderService::class)->accept(CustomerOrderRequest::findOrFail($payload['request']), $actor),
        'transfer' => app(PaymentService::class)->confirmCustomerBankTransfer(Order::findOrFail($payload['order']), $payload['attributes'], $actor),
    };
    echo json_encode(['status' => 'ok', 'id' => $result->getKey()], JSON_THROW_ON_ERROR);
} catch (ValidationException $exception) {
    echo json_encode(['status' => 'rejected', 'fields' => array_keys($exception->errors())], JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    echo json_encode(['status' => 'error', 'mode' => $payload['mode'], 'class' => $exception::class,
        'model' => $exception instanceof ModelNotFoundException ? $exception->getModel() : null,
        'driver_error' => $exception instanceof QueryException ? $exception->errorInfo : null], JSON_THROW_ON_ERROR);
    exit(1);
}
