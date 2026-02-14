<?php

namespace App\Services\MailTrap;

use App\Services\EmailService;
use Illuminate\Support\Facades\Log;

/**
 * MailTrapService - Backward compatible wrapper for EmailService.
 *
 * This service now delegates to EmailService which supports multiple providers.
 * You can switch providers via config or by passing provider name to methods.
 *
 * Supported providers: 'mailtrap', 'sendgrid', 'aws_ses', 'laravel_mail'
 * Configure via: services.mail_provider in config/services.php
 */
class MailTrapService
{
    public function __construct(protected EmailService $emailService)
    {
    }

    /**
     * Send email using configured provider (backward compatible).
     */
    public function sendEmail(array $data, ?string $provider = null): bool
    {
        try {
            return $this->emailService->sendRaw($data, $provider);
        } catch (\Exception $e) {
            Log::error('MailTrap service error', [
                'error' => $e->getMessage(),
                'provider' => $provider,
            ]);

            return false;
        }
    }

    /**
     * Send vend (bill payment) email wrapper.
     */
    public function sendVendEmail($user, array $vendData, ?string $provider = null): bool
    {
        return $this->emailService->sendVendNotification($user, $vendData, $provider);
    }

    /**
     * Send meter check email wrapper.
     */
    public function sendMeterCheckEmail($user, array $meterData, ?string $provider = null): bool
    {
        return $this->emailService->sendMeterCheckNotification($user, $meterData, $provider);
    }

    /**
     * Send transaction receipt email wrapper.
     */
    public function sendTransactionEmail($user, array $transaction, ?string $provider = null): bool
    {
        return $this->emailService->sendTransactionNotification($user, $transaction, $provider);
    }

    /**
     * Get current configured email provider.
     */
    public function getConfiguredProvider(): string
    {
        return $this->emailService->getConfiguredProvider();
    }

    /**
     * Get all available email providers.
     */
    public function getAvailableProviders(): array
    {
        return $this->emailService->getAvailableProviders();
    }
}
