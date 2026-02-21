<?php

namespace App\Http\Controllers\API;

use App\DTOs\ElectricityVendDTO;
use App\Http\Controllers\Controller;
use App\Jobs\SendPaymentReceiptJob;
use App\Models\Transaction;
use App\Services\ElectricityService;
use App\Services\EntertainmentService;
use App\Services\PaystackService;
use App\Services\TelecomsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        protected PaystackService $paystackService,
        protected ElectricityService $electricityService,
        protected TelecomsService $telecomsService,
        protected EntertainmentService $entertainmentService
    ) {}

    /**
     * Initialize payment for a bill.
     */
    public function initialize(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'amount' => 'required|numeric|min:100', // Minimum 100 NGN
            'type' => 'required|in:electricity,telecoms,entertainment',
            'bill_data' => 'required|array',
            'provider' => 'nullable|string',
        ]);

        $reference = 'PAY_'.uniqid().'_'.time();
        $originalAmount = $request->amount;
        $fee = 0;
        $tax = 0;

        if (in_array($request->type, ['electricity', 'entertainment'])) {
            $fee = 100;
            $tax = $originalAmount * 0.0015; // 0.15% tax
        }

        $totalAmount = $originalAmount + $fee + $tax;

        // Create transaction record
        $transaction = Transaction::create([
            'user_id' => auth()->id(),
            'reference' => $reference,
            'type' => $request->type,
            'amount' => $totalAmount,
            'status' => 'pending_payment',
            'provider_name' => $request->provider ?? config('billpayment.provider', 'buypower'),
            'meta' => [
                'bill_data' => $request->bill_data,
                'gateway' => 'paystack',
                'original_amount' => $originalAmount,
                'fee' => $fee,
                'tax' => $tax,
            ],
        ]);

        // Initialize Paystack
        $paystackData = [
            'email' => $request->email,
            'amount' => $totalAmount,
            'reference' => $reference,
            'metadata' => [
                'transaction_id' => $transaction->id,
                'type' => $request->type,
                'original_amount' => $originalAmount,
            ],
        ];

        $response = $this->paystackService->initializeTransaction($paystackData);

        if ($response['status'] ?? false) {
            return $this->success([
                'authorization_url' => $response['data']['authorization_url'],
                'access_code' => $response['data']['access_code'],
                'reference' => $reference,
            ], 'Payment initialized successfully.');
        }

        $transaction->update(['status' => 'failed', 'meta' => array_merge($transaction->meta, ['error' => $response['message']])]);

        return $this->error('Failed to initialize payment: '.($response['message'] ?? 'Unknown error'), 400);
    }

    /**
     * Handle Paystack Payment Callback (redirect after payment).
     *
     * Paystack redirects here with ?trxref=XXX&reference=XXX after the user
     * completes payment on the Paystack checkout page.
     */
    public function callback(Request $request)
    {
        $reference = $request->query('reference') ?? $request->query('trxref');

        if (! $reference) {
            return response()->json([
                'status' => false,
                'message' => 'No payment reference provided.',
            ], 400);
        }

        // Verify the payment with Paystack
        $verification = $this->paystackService->verifyTransaction($reference);

        if (! ($verification['status'] ?? false)) {
            Log::warning('Paystack callback: verification failed', [
                'reference' => $reference,
                'response' => $verification,
            ]);

            return response()->json([
                'status' => false,
                'message' => $verification['message'] ?? 'Payment verification failed.',
            ], 402);
        }

        $paymentData = $verification['data'];

        if ($paymentData['status'] !== 'success') {
            Log::warning('Paystack callback: payment not successful', [
                'reference' => $reference,
                'payment_status' => $paymentData['status'],
                'gateway_response' => $paymentData['gateway_response'] ?? null,
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Payment was not successful: '.($paymentData['gateway_response'] ?? $paymentData['status']),
                'data' => [
                    'reference' => $reference,
                    'payment_status' => $paymentData['status'],
                    'gateway_response' => $paymentData['gateway_response'] ?? null,
                ],
            ], 402);
        }

        // Find the local transaction record
        $transaction = Transaction::where('reference', $reference)->first();

        if (! $transaction) {
            Log::error('Paystack callback: transaction not found', ['reference' => $reference]);

            return response()->json([
                'status' => false,
                'message' => 'Transaction record not found for reference: '.$reference,
            ], 404);
        }

        // Idempotency: already processed
        if ($transaction->status !== 'pending_payment') {
            return response()->json([
                'status' => true,
                'message' => 'Payment already processed.',
                'data' => [
                    'reference' => $reference,
                    'transaction_status' => $transaction->status,
                ],
            ]);
        }

        // Process the successful payment (updates status + triggers bill vending)
        $this->processSuccessfulPayment($transaction, $paymentData);

        Log::info('Paystack callback: payment verified and processed', ['reference' => $reference]);

        // Redirect to frontend receipt page
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:8001'); // Fallback to 8001 for customer-portal

        return redirect($frontendUrl.'/bills/receipt?reference='.$reference);
    }

    /**
     * Handle Paystack Webhook.
     */
    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('x-paystack-signature');

        if (! $this->paystackService->isValidWebhook($payload, $signature)) {
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        $event = json_decode($payload, true);
        Log::info('Paystack Webhook Received', ['event' => $event['event']]);

        if ($event['event'] === 'charge.success') {
            $data = $event['data'];
            $reference = $data['reference'];

            $transaction = Transaction::where('reference', $reference)->first();

            if ($transaction && $transaction->status === 'pending_payment') {
                $this->processSuccessfulPayment($transaction, $data);
            }
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Process successful payment and trigger vending.
     */
    protected function processSuccessfulPayment(Transaction $transaction, array $paymentData)
    {
        $transaction->update([
            'status' => 'paid',
            'meta' => array_merge($transaction->meta, ['payment_details' => $paymentData]),
        ]);

        // ── Dispatch email + SMS receipt job ──────────────────────────────────
        $billData = $transaction->meta['bill_data'] ?? [];
        $user = [
            'email' => $paymentData['customer']['email'] ?? $billData['email'] ?? '',
            'first_name' => $paymentData['customer']['first_name'] ?? $billData['customer_name'] ?? 'Customer',
            'last_name' => $paymentData['customer']['last_name'] ?? '',
            'phone' => $billData['phone'] ?? null,
        ];

        SendPaymentReceiptJob::dispatch($transaction, $user, $paymentData);

        try {
            $vendAmount = $transaction->meta['original_amount'] ?? $transaction->amount;

            switch ($transaction->type) {
                case 'electricity':
                    $dto = new ElectricityVendDTO(
                        $billData['meter_number'],
                        $billData['disco'],
                        $vendAmount,
                        $billData['customer_name'] ?? 'Customer',
                        $billData['phone'] ?? '',
                        $billData['email'] ?? ''
                    );
                    $this->electricityService->vend($dto, $transaction->provider_name, $transaction);
                    break;

                case 'telecoms':
                    $vendingData = array_merge($billData, ['amount' => $vendAmount]);
                    $this->telecomsService->purchaseAirtimeOrData($vendingData, $transaction->provider_name, $transaction);
                    break;

                case 'entertainment':
                    $vendingData = array_merge($billData, ['amount' => $vendAmount]);
                    $this->entertainmentService->purchaseSubscription($vendingData, $transaction->provider_name, $transaction);
                    break;
            }

            Log::info('Bill vending triggered after payment success', ['reference' => $transaction->reference]);

        } catch (\Exception $e) {
            Log::error('Vending failed after successful payment', [
                'reference' => $transaction->reference,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
