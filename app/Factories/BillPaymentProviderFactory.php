<?php

namespace App\Factories;

use App\Contracts\BillPaymentProviderInterface;
use App\Providers\BillPayment\BuyPowerProvider;
use App\Providers\BillPayment\VTPassProvider;
use App\Providers\BillPayment\InterswitchProvider;
use InvalidArgumentException;

class BillPaymentProviderFactory
{
    protected array $providers = [
        'buypower' => BuyPowerProvider::class,
        'vtpass' => VTPassProvider::class,
        'interswitch' => InterswitchProvider::class,
    ];

    /**
     * Create a bill payment provider instance.
     */
    public function make(?string $provider = null): BillPaymentProviderInterface
    {
        $provider = $provider ?? config('billpayment.provider', 'buypower');

        if (!isset($this->providers[$provider])) {
            throw new InvalidArgumentException("Unsupported bill payment provider: {$provider}");
        }

        return app($this->providers[$provider]);
    }

    /**
     * Get list of available providers.
     */
    public function availableProviders(): array
    {
        return array_keys($this->providers);
    }
}
