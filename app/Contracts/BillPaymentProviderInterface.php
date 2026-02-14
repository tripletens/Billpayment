<?php

namespace App\Contracts;

use App\DTOs\ElectricityVendDTO;
use App\DTOs\MeterCheckResponseDTO;
use App\DTOs\TransactionResponseDTO;

interface BillPaymentProviderInterface
{
    /**
     * Vend electricity to a meter.
     */
    public function vendElectricity(ElectricityVendDTO $dto): array;

    /**
     * Check meter details.
     */
    public function checkMeter(string $meter, string $disco, string $vendType): MeterCheckResponseDTO;

    /**
     * Get transaction details by order ID.
     */
    public function getTransaction(string $orderId): TransactionResponseDTO;

    /**
     * Get the provider name.
     */
    public function getName(): string;
}
