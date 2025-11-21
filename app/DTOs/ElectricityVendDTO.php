<?php

namespace App\DTOs;

class ElectricityVendDTO
{
    public function __construct(
        public readonly string $meterNumber,
        public readonly string $disco,
        public readonly float $amount,
        public readonly string $customerName,
        public readonly string $phone
    ) {}
}
