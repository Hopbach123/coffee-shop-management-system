<?php

namespace App\Data\Payments;

final readonly class SettlementSnapshot
{
    public function __construct(
        public int $orderId,
        public string $expectedAmount,
        public string $confirmedAmount,
        public bool $paidInFull,
        public array $paymentReferences,
    ) {}
}
