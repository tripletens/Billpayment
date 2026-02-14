<?php

namespace App\DTOs;

readonly class WalletTransactionDTO
{
    public function __construct(
        public int $id,
        public string $ref,
        public string $operation,
        public string $type,
        public string $balance_before,
        public string $balance_after,
        public string $created_at,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            ref: (string) $data['ref'],
            operation: (string) $data['operation'],
            type: (string) $data['type'],
            balance_before: (string) $data['balance_before'],
            balance_after: (string) $data['balance_after'],
            created_at: (string) $data['created_at'],
        );
    }
}
