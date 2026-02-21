<?php

namespace App\Services;

use App\Factories\EmailProviderFactory;
use App\Factories\MailServiceProviderFactory;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;

/**
 * Email Service - Unified interface for sending emails through different providers.
 * Supports switching between MailTrap, SendGrid, AWS SES, and Laravel Mail.
 */
class EmailService
{
    public function __construct(
        protected EmailProviderFactory $providerFactory,
        protected MailServiceProviderFactory $mailServiceFactory
    ) {}

    /**
     * Send email with Mailable (Laravel native).
     */
    public function send(string $to, Mailable $mailable, ?string $providerName = null): void
    {
        $provider = $this->providerFactory->make($providerName);
        $provider->send($to, $mailable);
    }

    /**
     * Send email with raw data (through configurable providers).
     *
     * @param  array  $data  Email data
     * @param  string|null  $provider  Provider name
     */
    public function sendRaw(array $data, ?string $provider = null): bool
    {
        try {
            $mailProvider = $provider
                ? $this->mailServiceFactory->make($provider)
                : $this->mailServiceFactory->getConfigured();

            return $mailProvider->send($data);
        } catch (\Exception $e) {
            Log::error('Email service error', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            // Fallback to Laravel Mail
            return $this->mailServiceFactory->make('laravel_mail')->send($data);
        }
    }

    /**
     * Send a vend (bill payment) notification email to user.
     */
    public function sendVendNotification($user, array $vendData, ?string $provider = null): bool
    {
        $htmlContent = view('emails.bill_vend_success', [
            'user' => $user,
            'vend' => $vendData,
        ])->render();

        return $this->sendRaw([
            'email' => is_array($user) ? ($user['email'] ?? '') : $user->email,
            'name' => is_array($user) ? ($user['name'] ?? "{$user['first_name']} {$user['last_name']}") : ($user->first_name.' '.$user->last_name),
            'subject' => 'Bill Payment Successful',
            'text' => 'Your bill payment was successful.',
            'html' => $htmlContent,
            'category' => 'BillPayment',
            'from' => [
                'email' => 'noreply@lythubtechnologies.com',
                'name' => 'BillPayment Service',
            ],
            'cc' => [],
            'bcc' => [],
            'reply_to' => [
                'email' => 'support@lythubtechnologies.com',
                'name' => 'Support',
            ],
        ], $provider);
    }

    /**
     * Send meter check result email.
     */
    public function sendMeterCheckNotification($user, array $meterData, ?string $provider = null): bool
    {
        $htmlContent = view('emails.meter_check_result', [
            'user' => $user,
            'meter' => $meterData,
        ])->render();

        return $this->sendRaw([
            'email' => is_array($user) ? ($user['email'] ?? '') : $user->email,
            'name' => is_array($user) ? ($user['name'] ?? "{$user['first_name']} {$user['last_name']}") : ($user->first_name.' '.$user->last_name),
            'subject' => 'Meter Check Result',
            'text' => 'Meter check completed.',
            'html' => $htmlContent,
            'category' => 'MeterCheck',
            'from' => [
                'email' => 'noreply@lythubtechnologies.com',
                'name' => 'BillPayment Service',
            ],
        ], $provider);
    }

    /**
     * Send transaction receipt/notification email for a bill payment.
     */
    public function sendTransactionNotification($user, array $transaction, ?string $provider = null): bool
    {
        $htmlContent = view('emails.transaction_receipt', [
            'user' => $user,
            'transaction' => $transaction,
        ])->render();

        return $this->sendRaw([
            'email' => is_array($user) ? ($user['email'] ?? '') : $user->email,
            'name' => is_array($user) ? ($user['name'] ?? "{$user['first_name']} {$user['last_name']}") : ($user->first_name.' '.$user->last_name),
            'subject' => 'Transaction Receipt',
            'text' => 'Your transaction was processed.',
            'html' => $htmlContent,
            'category' => 'Transaction',
            'from' => [
                'email' => 'noreply@lythubtechnologies.com',
                'name' => 'BillPayment Service',
            ],
        ], $provider);
    }

    /**
     * Send transaction receipt email with a PDF attachment.
     *
     * @param  array|object  $user  User info (array or model)
     * @param  array  $transactionData  Transaction data for the PDF
     * @param  string  $pdfPath  Absolute path to the generated PDF file
     * @param  string|null  $provider  Optional mail provider override
     */
    public function sendReceiptWithPdf(
        array|object $user,
        array $transactionData,
        string $pdfPath,
        ?string $provider = null
    ): bool {
        $email = is_array($user) ? ($user['email'] ?? '') : $user->email;
        $name = is_array($user)
            ? ($user['name'] ?? trim(($user['first_name'] ?? '').' '.($user['last_name'] ?? '')))
            : trim($user->first_name.' '.$user->last_name);

        $htmlContent = view('emails.pdf_receipt', [
            'user' => $user,
            'transaction' => $transactionData,
        ])->render();

        return $this->sendRaw([
            'email' => $email,
            'name' => $name,
            'subject' => 'Your Payment Receipt — '.($transactionData['reference'] ?? ''),
            'text' => 'Your payment was successful. Please find your receipt attached.',
            'html' => $htmlContent,
            'category' => 'PaymentReceipt',
            'from' => [
                'email' => 'noreply@lythubtechnologies.com',
                'name' => 'BillPayment Service',
            ],
            'cc' => [],
            'bcc' => [],
            'reply_to' => [
                'email' => 'support@lythubtechnologies.com',
                'name' => 'Support',
            ],
            'attachments' => [
                [
                    'path' => $pdfPath,
                    'name' => 'receipt_'.($transactionData['reference'] ?? 'payment').'.pdf',
                    'mime' => 'application/pdf',
                ],
            ],
        ], $provider);
    }

    /**
     * Get current configured provider.
     */
    public function getConfiguredProvider(): string
    {
        $provider = $this->mailServiceFactory->getConfigured();

        return $provider->getName();
    }

    /**
     * Get all available providers.
     */
    public function getAvailableProviders(): array
    {
        return $this->mailServiceFactory->getAvailable();
    }
}
