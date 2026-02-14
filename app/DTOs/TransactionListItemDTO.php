<?php

namespace App\DTOs;

readonly class TransactionListItemDTO
{
    /**
     * @param WalletTransactionDTO[] $walletTransactions
     */
    public function __construct(
        public int $id,
        public string $order_id,
        public string $phone,
        public string $name,
        public string $meter_no,
        public string $amount,
        public string $vend_type,
        public string $vertical_type,
        public string $disco_code,
        public string $payment_status,
        public string $vend_status,
        public string $created_at,
        public ?VendResponseDTO $vendResponse = null,
        public array $walletTransactions = [],
    ) {}

    public static function fromArray(array $data): self
    {
        $walletTransactions = [];
        if (isset($data['walletTransactions']) && is_array($data['walletTransactions'])) {
            $walletTransactions = array_map(
                fn(array $item) => WalletTransactionDTO::fromArray($item),
                $data['walletTransactions']
            );
        }

        return new self(
            id: (int) $data['id'],
            order_id: (string) $data['order_id'],
            phone: (string) $data['phone'],
            name: (string) $data['name'],
            meter_no: (string) $data['meter_no'],
            amount: (string) $data['amount'],
            vend_type: (string) $data['vend_type'],
            vertical_type: (string) $data['vertical_type'],
            disco_code: (string) $data['disco_code'],
            payment_status: (string) $data['payment_status'],
            vend_status: (string) $data['vend_status'],
            created_at: (string) $data['created_at'],
            vendResponse: VendResponseDTO::fromArray($data['vendResponse'] ?? null),
            walletTransactions: $walletTransactions,
        );
    }
}
