<?php

namespace App\Factories;

use App\Contracts\MailServiceProviderInterface;
use App\Providers\Mail\AwsSesProvider;
use App\Providers\Mail\LaravelMailProvider;
use App\Providers\Mail\MailTrapProvider;
use App\Providers\Mail\SendGridProvider;
use InvalidArgumentException;

class MailServiceProviderFactory
{
    protected array $providers = [
        'mailtrap' => MailTrapProvider::class,
        'sendgrid' => SendGridProvider::class,
        'aws_ses' => AwsSesProvider::class,
        'laravel_mail' => LaravelMailProvider::class,
    ];

    /**
     * Create a mail service provider instance.
     *
     * @param string|null $provider Provider name or null to use config default
     * @return MailServiceProviderInterface
     * @throws InvalidArgumentException
     */
    public function make(?string $provider = null): MailServiceProviderInterface
    {
        $provider = $provider ?? config('services.mail_provider', 'mailtrap');

        if (!isset($this->providers[$provider])) {
            throw new InvalidArgumentException("Unsupported mail provider: {$provider}");
        }

        return app($this->providers[$provider]);
    }

    /**
     * Register a custom provider.
     */
    public function register(string $name, string $class): self
    {
        $this->providers[$name] = $class;

        return $this;
    }

    /**
     * Get all available providers.
     */
    public function getAvailable(): array
    {
        return array_keys($this->providers);
    }

    /**
     * Attempt to get first configured and available provider.
     */
    public function getConfigured(): ?MailServiceProviderInterface
    {
        $preferredProviders = [
            config('services.mail_provider', 'mailtrap'),
            'mailtrap',
            'sendgrid',
            'aws_ses',
            'laravel_mail',
        ];

        foreach (array_unique($preferredProviders) as $provider) {
            try {
                $instance = $this->make($provider);
                if ($instance->isConfigured()) {
                    return $instance;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // Fallback to Laravel Mail (always available)
        return $this->make('laravel_mail');
    }
}
