<?php

namespace App\DTOs;

readonly class WalletBalanceDTO
{
    public function __construct(
        public string $balance,
        public string $currency = 'NGN',
        public string $lastUpdated = '',
        public float $threshold = 20000,
        public bool $lowBalanceAlert = false,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            balance: (string) ($data['balance'] ?? '0.00'),
            currency: $data['currency'] ?? 'NGN',
            lastUpdated: $data['last_updated'] ?? now()->toIso8601String(),
            threshold: (float) ($data['threshold'] ?? 20000),
            lowBalanceAlert: (bool) ($data['low_balance_alert'] ?? false),
        );
    }
}
