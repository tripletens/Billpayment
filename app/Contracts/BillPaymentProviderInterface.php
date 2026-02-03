<?php

namespace App\Contracts;

use App\DTOs\ElectricityVendDTO;

interface BillPaymentProviderInterface
{
    /**
     * Vend electricity to a meter.
     */
    public function vendElectricity(ElectricityVendDTO $dto): array;

    /**
     * Get the provider name.
     */
    public function getName(): string;
}
