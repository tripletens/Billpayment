<?php

namespace App\DTOs;

readonly class VendResponseDTO
{
    public function __construct(
        public ?int $id = null,
        public ?string $payment_reference = null,
        public ?string $token = null,
        public ?int $order_id = null,
        public ?string $rct_num = null,
        public ?string $response_message = null,
        public ?string $meter_category = null,
        public ?string $amt_electricity = null,
        public ?string $debt_rem = null,
    ) {}

    public static function fromArray(?array $data): ?self
    {
        if (!$data) {
            return null;
        }

        return new self(
            id: $data['id'] ?? null,
            payment_reference: $data['payment_reference'] ?? null,
            token: $data['token'] ?? null,
            order_id: $data['order_id'] ?? null,
            rct_num: $data['rct_num'] ?? null,
            response_message: $data['response_message'] ?? null,
            meter_category: $data['meter_category'] ?? null,
            amt_electricity: $data['amt_electricity'] ?? null,
            debt_rem: $data['debt_rem'] ?? null,
        );
    }
}
