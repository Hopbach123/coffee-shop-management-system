<?php

use App\Http\Controllers\Public\CartController;
use App\Http\Controllers\Public\OrderTrackingController;
use App\Models\OnlineOrderRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\OnlineRequestSubmissionService;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\PromotionService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

class QaCheckoutSessionMiddleware
{
    public static array $fixture;

    public function handle($request, $next)
    {
        $input = self::$fixture;
        $request->session()->put('customer_cart', [$input['variant'] => 1]);
        $request->session()->put('checkout_intent', ['key' => $input['attributes']['submission_key'], 'token' => $input['plain_token']]);
        app(CartController::class)->checkout($request);

        return $next($request);
    }
}

try {
    $input = json_decode(stream_get_contents(STDIN), true, 32, JSON_THROW_ON_ERROR);
    if (! preg_match('/\Asales_finance_test_[0-9a-f]{16}\z/', $input['connection']['database'] ?? '')
        || ($input['connection']['host'] ?? '') !== '127.0.0.1') {
        throw new RuntimeException('Unsafe isolated target.');
    }
    config(['database.default' => 'qa_worker', 'database.connections.qa_worker' => $input['connection']]);
    DB::purge('qa_worker');
    if ($input['command'] === 'stale_complete') {
        DB::beginTransaction();
        DB::table('payments')->where('order_id', $input['order'])->get();
    }
    echo "READY\n";
    flush();
    $deadline = microtime(true) + 20;
    while (! is_file($input['barrier'])) {
        if (microtime(true) > $deadline) {
            throw new RuntimeException('Barrier timeout.');
        }
        usleep(10000);
    }
    $actor = User::findOrFail($input['actor']);
    $order = isset($input['order']) ? Order::findOrFail($input['order']) : null;
    $sales = app(OrderService::class);
    if ($input['command'] === 'stale_complete') {
        config(['database.connections.qa_observer' => $input['connection']]);
        $deadline = microtime(true) + 15;
        while (! DB::connection('qa_observer')->table('payments')->where('order_id', $input['order'])->where('status', 'paid')->exists()) {
            if (microtime(true) > $deadline) {
                throw new RuntimeException('Payment observer timeout.');
            }
            usleep(10000);
        }
    }
    if ($input['command'] === 'submit_http') {
        // Independent HTTP kernels/session stores carry the same frozen checkout intent.
        $app->instance('env', 'testing');
        config(['app.env' => 'testing', 'session.driver' => 'array', 'cache.default' => 'array']);
        QaCheckoutSessionMiddleware::$fixture = $input;
        Route::post('/qa/submit', [CartController::class, 'place'])
            ->middleware(['web', QaCheckoutSessionMiddleware::class]);
        Route::get('/orders/track/{token}', [OrderTrackingController::class, 'show'])->name('orders.track');
        Route::getRoutes()->refreshNameLookups();
        $request = Request::create('/qa/submit', 'POST', [
            'purchase_type' => 'online', 'customer_name' => $input['attributes']['contact_name'],
            'customer_phone' => $input['attributes']['contact_phone'],
            'submission_key' => $input['attributes']['submission_key'],
        ]);
        $kernel = app(Illuminate\Contracts\Http\Kernel::class);
        $response = $kernel->handle($request);
        $record = OnlineOrderRequest::where('submission_key', $input['attributes']['submission_key'])->first();
        if ($response->getStatusCode() !== 302 || ! $record || ! str_contains((string) $response->headers->get('Location'), '/orders/track/')) {
            throw new RuntimeException('HTTP submit failed, status '.$response->getStatusCode());
        }
        $kernel->terminate($request, $response);
        echo json_encode(['outcome' => 'success', 'id' => $record->id, 'http_status' => $response->getStatusCode()], JSON_THROW_ON_ERROR)."\n";
        exit(0);
    }
    $result = match ($input['command']) {
        'pay' => app(PaymentService::class)->recordPaid($order, $input['payload'], $actor),
        'cancel' => $sales->cancel($order, $actor),
        'serve' => $sales->transitionItemStatus($order, OrderItem::findOrFail($input['item']), 'served', $actor),
        'complete' => $sales->complete($order, $actor),
        'stale_complete' => $sales->complete($order, $actor),
        'prepare' => $sales->transitionItemStatus($order, OrderItem::findOrFail($input['item']), 'preparing', $actor),
        'accept' => $sales->acceptOnlineRequest(OnlineOrderRequest::findOrFail($input['request']), $actor),
        'submit' => app(OnlineRequestSubmissionService::class)->submit($input['attributes'], $input['items']),
        'promote' => app(PromotionService::class)->applyToOrder($order, $input['code']),
        default => throw new RuntimeException('Unsupported command.'),
    };
    if ($input['command'] === 'stale_complete') {
        DB::commit();
    }
    echo json_encode(['outcome' => 'success', 'id' => $result->getKey()], JSON_THROW_ON_ERROR)."\n";
} catch (ValidationException|DomainException $exception) {
    echo json_encode(['outcome' => 'rejected', 'kind' => get_class($exception)], JSON_THROW_ON_ERROR)."\n";
} catch (Throwable $exception) {
    // Never print connection strings, SQL payloads or receipt/customer data.
    echo json_encode(['outcome' => 'error', 'kind' => get_class($exception), 'code' => (string) $exception->getCode(), 'file' => basename($exception->getFile()), 'line' => $exception->getLine()], JSON_THROW_ON_ERROR)."\n";
    exit(1);
}
