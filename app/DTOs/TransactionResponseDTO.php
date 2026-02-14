<?php

namespace App\DTOs;

class TransactionResponseDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $amountGenerated,
        public readonly string $disco,
        public readonly string $debtAmount,
        public readonly string $debtRemaining,
        public readonly string $orderId,
        public readonly string $receiptNo,
        public readonly string $tax,
        public readonly string $vendTime,
        public readonly string $token,
        public readonly float $totalAmountPaid,
        public readonly string $units,
        public readonly string $vendAmount,
        public readonly string $vendRef,
        public readonly int $responseCode,
        public readonly string $responseMessage
    ) {}

    /**
     * Create DTO from API response array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            amountGenerated: (string) ($data['amountGenerated'] ?? '0'),
            disco: (string) ($data['disco'] ?? ''),
            debtAmount: (string) ($data['debtAmount'] ?? '0'),
            debtRemaining: (string) ($data['debtRemaining'] ?? '0'),
            orderId: (string) ($data['orderId'] ?? ''),
            receiptNo: (string) ($data['receiptNo'] ?? ''),
            tax: (string) ($data['tax'] ?? '0'),
            vendTime: (string) ($data['vendTime'] ?? ''),
            token: (string) ($data['token'] ?? ''),
            totalAmountPaid: (float) ($data['totalAmountPaid'] ?? 0),
            units: (string) ($data['units'] ?? '0'),
            vendAmount: (string) ($data['vendAmount'] ?? '0'),
            vendRef: (string) ($data['vendRef'] ?? ''),
            responseCode: (int) ($data['responseCode'] ?? 0),
            responseMessage: (string) ($data['responseMessage'] ?? '')
        );
    }
}
