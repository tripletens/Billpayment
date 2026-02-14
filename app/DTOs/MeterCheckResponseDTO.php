<?php

namespace App\DTOs;

class MeterCheckResponseDTO
{
    public function __construct(
        public readonly bool $error,
        public readonly string $discoCode,
        public readonly string $vendType,
        public readonly string $meterNo,
        public readonly float $minVendAmount,
        public readonly float $maxVendAmount,
        public readonly int $responseCode,
        public readonly float $outstanding,
        public readonly float $debtRepayment,
        public readonly string $name,
        public readonly string $address,
        public readonly string $tariff,
        public readonly string $tariffClass
    ) {}

    /**
     * Create DTO from API response array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            error: (bool) ($data['error'] ?? false),
            discoCode: (string) ($data['discoCode'] ?? ''),
            vendType: (string) ($data['vendType'] ?? ''),
            meterNo: (string) ($data['meterNo'] ?? ''),
            minVendAmount: (float) ($data['minVendAmount'] ?? 0),
            maxVendAmount: (float) ($data['maxVendAmount'] ?? 0),
            responseCode: (int) ($data['responseCode'] ?? 0),
            outstanding: (float) ($data['outstanding'] ?? 0),
            debtRepayment: (float) ($data['debtRepayment'] ?? 0),
            name: (string) ($data['name'] ?? ''),
            address: (string) ($data['address'] ?? ''),
            tariff: (string) ($data['tariff'] ?? ''),
            tariffClass: (string) ($data['tariffClass'] ?? '')
        );
    }
}
