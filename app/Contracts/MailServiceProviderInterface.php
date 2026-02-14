<?php

namespace App\Contracts;

/**
 * Interface for email service providers that support raw email sending.
 * This allows switching between MailTrap, SendGrid, AWS SES, etc.
 */
interface MailServiceProviderInterface
{
    /**
     * Send email with raw data.
     *
     * @param array $data Email data including to, from, subject, text, html, etc.
     * @return bool Success status
     */
    public function send(array $data): bool;

    /**
     * Get the provider name.
     */
    public function getName(): string;

    /**
     * Check if provider is configured and available.
     */
    public function isConfigured(): bool;
}
