<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Services\EmailService;
use App\Services\TermiiService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SendPaymentReceiptJob implements ShouldQueue
{
    use Queueable;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Create a new job instance.
     *
     * @param  Transaction  $transaction  The paid transaction
     * @param  array        $user         User info: ['email', 'name'/'first_name'/'last_name', 'phone'?]
     * @param  array        $paymentData  Raw Paystack payment data (channel, paid_at, etc.)
     */
    public function __construct(
        protected Transaction $transaction,
        protected array $user,
        protected array $paymentData = []
    ) {}

    /**
     * Execute the job.
     */
    public function handle(EmailService $emailService, TermiiService $termiiService): void
    {
        $reference = $this->transaction->reference;

        Log::info('SendPaymentReceiptJob: starting', ['reference' => $reference]);

        // ── 1. Build transaction data for the PDF ──────────────────────────────
        $transactionData = array_merge(
            $this->transaction->toArray(),
            [
                'channel'  => $this->paymentData['channel'] ?? null,
                'paid_at'  => $this->paymentData['paid_at'] ?? $this->transaction->updated_at,
            ]
        );

        // ── 2. Generate PDF ────────────────────────────────────────────────────
        $pdfPath = null;

        try {
            $pdf = Pdf::loadView('emails.pdf_receipt', [
                'user'        => $this->user,
                'transaction' => $transactionData,
            ]);

            $filename = 'receipts/' . $reference . '.pdf';
            Storage::put($filename, $pdf->output());
            $pdfPath = Storage::path($filename);

        } catch (\Exception $e) {
            Log::error('SendPaymentReceiptJob: PDF generation failed', [
                'reference' => $reference,
                'error'     => $e->getMessage(),
            ]);
        }

        // ── 3. Send email ──────────────────────────────────────────────────────
        try {
            if ($pdfPath && file_exists($pdfPath)) {
                $emailService->sendReceiptWithPdf($this->user, $transactionData, $pdfPath);
            } else {
                // Fallback: send without PDF
                $emailService->sendTransactionNotification($this->user, $transactionData);
            }

            Log::info('SendPaymentReceiptJob: email sent', ['reference' => $reference]);

        } catch (\Exception $e) {
            Log::error('SendPaymentReceiptJob: email failed', [
                'reference' => $reference,
                'error'     => $e->getMessage(),
            ]);
        }

        // ── 4. Send SMS (Termii — no-op if TERMII_ENABLED=false) ──────────────
        $phone = $this->user['phone']
            ?? $this->transaction->meta['bill_data']['phone']
            ?? null;

        if ($phone) {
            try {
                $termiiService->sendPaymentReceiptSms(
                    $phone,
                    $reference,
                    (float) $this->transaction->amount,
                    $this->transaction->type
                );
            } catch (\Exception $e) {
                Log::warning('SendPaymentReceiptJob: SMS failed (non-fatal)', [
                    'reference' => $reference,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        // ── 5. Clean up temp PDF ───────────────────────────────────────────────
        if ($pdfPath && file_exists($pdfPath)) {
            try {
                Storage::delete('receipts/' . $reference . '.pdf');
            } catch (\Exception $e) {
                Log::warning('SendPaymentReceiptJob: could not delete temp PDF', ['error' => $e->getMessage()]);
            }
        }

        Log::info('SendPaymentReceiptJob: completed', ['reference' => $reference]);
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendPaymentReceiptJob: job failed permanently', [
            'reference' => $this->transaction->reference,
            'error'     => $exception->getMessage(),
        ]);
    }
}
