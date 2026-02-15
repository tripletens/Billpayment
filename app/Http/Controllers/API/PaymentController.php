<?php

namespace App\Http\Controllers\API;

use App\DTOs\ElectricityVendDTO;
use App\Http\Controllers\Controller;
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

        try {
            $billData = $transaction->meta['bill_data'];
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
                    $this->electricityService->vend($dto, $transaction->provider_name);
                    break;

                case 'telecoms':
                    $vendingData = array_merge($billData, ['amount' => $vendAmount]);
                    $this->telecomsService->purchaseAirtimeOrData($vendingData, $transaction->provider_name);
                    break;

                case 'entertainment':
                    $vendingData = array_merge($billData, ['amount' => $vendAmount]);
                    $this->entertainmentService->purchaseSubscription($vendingData, $transaction->provider_name);
                    break;
            }

            Log::info('Bill vending triggered after payment success', ['reference' => $transaction->reference]);

        } catch (\Exception $e) {
            Log::error('Vending failed after successful payment', [
                'reference' => $transaction->reference,
                'error' => $e->getMessage(),
            ]);
            // Transaction status will be updated by the service's vend method usually,
            // but we might want to flag this specifically as 'payment_success_vending_failed'
        }
    }
}
