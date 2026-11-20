<?php

namespace App\Data\Payments;

final readonly class SettlementResult
{
    public function __construct(
        public int $paymentId,
        public string $paymentReference,
        public int $orderId,
        public string $confirmedAmount,
        public string $method,
        public string $paidAt,
        public bool $replay,
        public string $receivedAmount,
        public string $changeAmount,
    ) {}
}
