<?php

namespace App\Http\Controllers\v1\Webhook;

use App\Events\PushNotificationEvent;
use App\Helpers\PaymentLogger;
use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\NombaTransaction;
use App\Models\TransactionLog;
use App\Models\User;
use App\Models\VirtualAccount;
use App\Notifications\NombaFundedWallet;
use App\Notifications\NombaPaymentFailed;
use App\Notifications\NombaPaymentReversed;
use App\Notifications\NombaPayoutFailed;
use App\Notifications\NombaPayoutRefunded;
use App\Notifications\NombaTransferSuccessful;
use App\Notifications\NombaVirtualAccountDepositNotification;
use App\Services\ActivityTracker;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class webhook extends Controller
{
    private const SUPPORTED_EVENTS = [
        'payment_success',
        'payout_success',
        'payment_failed',
        'payment_reversal',
        'payout_failed',
        'payout_refund'
    ];

    private const STATUS_MAP = [
        'payment_success' => 'success',
        'payout_success' => 'success',
        'payment_failed' => 'failed',
        'payment_reversal' => 'reversed',
        'payout_failed' => 'failed',
        'payout_refund' => 'refunded',
        'transfer_failed' => 'failed',
        'transfer_reversed' => 'reversed',
    ];

    public function __construct(private ActivityTracker $tracker)
    {
    }

    /**
     * Handle Nomba webhook
     */
    public function nombaWebhook(Request $request): Response
    {
        try {
            PaymentLogger::log("Nomba webhook received", $request->all());

            # Validate payload structure
            $validatedData = $this->validateWebhookPayload($request);
            if (!$validatedData) {
                return response('Invalid payload structure', 400);
            }

            # Check if event is supported
            if (!in_array($validatedData['event_type'], self::SUPPORTED_EVENTS)) {
                PaymentLogger::log('Unsupported webhook event', ['event' => $validatedData['event_type']]);
                return response('Event not supported', 200);
            }

            # Process webhook with database transaction
            $result = DB::transaction(fn() => $this->processWebhook($validatedData));

            return response(
                $result['message'],
                $result['success'] ? 200 : ($result['status_code'] ?? 500)
            );

        } catch (\Exception $e) {
            PaymentLogger::error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response('Internal server error', 200);
        }
    }

    /**
     * Get transaction reference based on event type
     */
    private function getTransactionReference(array $data): ?string
    {
        return match ($data['event_type']) {
            'payment_success', 'payment_failed', 'payment_reversal' => $data['data']['order']['orderReference']
                ?? $data['data']['transaction']['merchantTxRef']
                ?? $data['data']['transaction']['transactionId'] ?? null,
            'payout_success', 'payout_failed', 'payout_refund' => $data['data']['transaction']['merchantTxRef'] ?? null,
            default => $data['data']['order']['orderReference']
                ?? $data['data']['transaction']['merchantTxRef']
                ?? $data['data']['transaction']['transactionId']
                ?? null
        };
    }

    /**
     * Get order ID based on event type
     */
    private function getOrderId(array $data): ?string
    {
        return match ($data['event_type']) {
            'payment_success', 'payment_failed', 'payment_reversal' => $data['data']['order']['orderId'] ?? null,
            'payout_success', 'payout_failed', 'payout_refund' => $data['data']['transaction']['transactionId'] ?? null,
            default => $data['data']['order']['orderId']
                ?? $data['data']['transaction']['transactionId']
                ?? null
        };
    }

    /**
     * Get transaction amount from payload
     */
    private function getTransactionAmount(array $data): float
    {
        return $data['data']['order']['amount']
            ?? $data['data']['transaction']['transactionAmount']
            ?? 0;
    }

    /**
     * Get currency from payload
     */
    private function getCurrency(array $data): string
    {
        return $data['data']['order']['currency']
            ?? $data['data']['order']['cardCurrency']
            ?? 'NGN';
    }

    /**
     * Get payment method from payload
     */
    private function getPaymentMethod(array $data): ?string
    {
        if (isset($data['data']['order']['paymentMethod'])) {
            return $data['data']['order']['paymentMethod'];
        }

        # Handle different transaction types when no order object exists
        if (isset($data['data']['transaction']['type'])) {
            return match ($data['data']['transaction']['type']) {
                'vact_transfer' => 'virtual_account',
                'transfer' => 'bank_transfer',
                'online_checkout' => 'card_payment',
                default => $data['data']['transaction']['type']
            };
        }

        return in_array($data['event_type'], ['payout_success', 'payout_failed', 'payout_refund'])
            ? 'bank_transfer' : null;
    }

    /**
     * Validate webhook payload structure
     */
    private function validateWebhookPayload(Request $request): ?array
    {
        $input = json_decode(json_encode($request->all()), true);

        PaymentLogger::log('Received webhook payload', [
            'event_type' => $input['event_type'] ?? 'unknown',
            'raw' => $input
        ]);

        return $input;
    }

    private function validateWebhookPayload001(Request $request): ?array
    {
        $baseRules = [
            'event_type' => 'required|string',
            'requestId' => 'required|string',
            'data' => 'required|array',
            'data.transaction.transactionId' => 'required|string',
            'data.transaction.fee' => 'nullable|numeric|min:0',
            'data.transaction.time' => 'nullable|string',
            'data.merchant.walletId' => 'nullable|string',
            'data.merchant.userId' => 'nullable|string',
        ];

        $eventType = $request->input('event_type');

        # Add event-specific validation rules
        if (in_array($eventType, ['payment_success', 'payment_failed', 'payment_reversal'])) {
            $baseRules = array_merge($baseRules, [
                'data.order.orderId' => 'required|string',
                'data.order.orderReference' => 'required|string',
                'data.order.amount' => 'required|numeric|min:0',
                'data.order.customerEmail' => 'nullable|email',
            ]);
        } elseif (in_array($eventType, ['payout_success', 'payout_failed', 'payout_refund'])) {
            $baseRules = array_merge($baseRules, [
                'data.transaction.merchantTxRef' => 'required|string',
                'data.transaction.transactionAmount' => 'required|numeric|min:0',
                'data.customer.accountNumber' => 'required|string',
                'data.customer.bankCode' => 'required|string',
            ]);
        }

        $validator = Validator::make($request->all(), $baseRules);

        if ($validator->fails()) {
            PaymentLogger::log('Invalid webhook payload', [
                'errors' => $validator->errors()->toArray(),
                'event_type' => $eventType
            ]);
            return null;
        }

        return $request->all();
    }

    /**
     * Process webhook based on event type
     */
    private function processWebhook(array $data): array
    {
        $reference = $this->getTransactionReference($data);
        $event = $data['event_type'];

        if (!$reference) {
            PaymentLogger::log('No reference found in webhook payload', ['event' => $event]);
            return ['success' => false, 'message' => 'No transaction reference found', 'status_code' => 400];
        }

        # Check for idempotency for success events only
        if (in_array($event, ['payment_success', 'payout_success']) && $this->isAlreadyProcessed($reference)) {
            PaymentLogger::log('Webhook already processed', ['reference' => $reference, 'event' => $event]);
            return ['success' => true, 'message' => 'Already processed'];
        }

        # Try to find existing transaction
        $transaction = $this->findExistingTransaction($reference);

        # Handle virtual account funding if no transaction found
        if (!$transaction && $this->isVirtualAccountFunding($data)) {
            return $this->handleVirtualAccountFunding($data);
        }

        # Process existing transaction
        if ($transaction) {
            return $this->processExistingTransaction($transaction, $data);
        }

        PaymentLogger::log('No matching transaction found', ['reference' => $reference, 'event' => $event]);
        return ['success' => false, 'message' => 'Transaction not found', 'status_code' => 404];
    }

    /**
     * Check if webhook has already been processed (for success events)
     */
    private function isAlreadyProcessed(string $reference): bool
    {
        return NombaTransaction::where('reference', $reference)
            ->where('status', 'success')
            ->exists();
    }

    /**
     * Find existing transaction by reference
     */
    private function findExistingTransaction(string $reference): ?TransactionLog
    {
        return TransactionLog::where('transaction_reference', $reference)
            ->where('provider', 'nomba')
            ->first();
    }

    /**
     * Check if this is virtual account funding
     */
    private function isVirtualAccountFunding(array $data): bool
    {
        return $data['event_type'] === 'payment_success' && (
                #  Traditional virtual account funding (has order object)
                (isset($data['data']['order']['paymentMethod'])
                    && $data['data']['order']['paymentMethod'] === 'card_payment')
                ||
                #  New virtual account transfer (no order object, check transaction type)
                (isset($data['data']['transaction']['type'])
                    && $data['data']['transaction']['type'] === 'vact_transfer'
                    && !isset($data['data']['order']))
            );
    }

    /**
     * Handle virtual account funding
     */
    private function handleVirtualAccountFunding(array $data): array
    {
        $customerEmail = $data['data']['order']['customerEmail'] ?? null;
        $amount = $this->getTransactionAmount($data);
        $reference = $this->getTransactionReference($data);
        $user = null;

        if (!$customerEmail && isset($data['data']['transaction']['aliasAccountNumber'])) {
            # Find user by virtual account number
            $receiver = $data['data']['transaction']['aliasAccountNumber'];
            $toReceive = VirtualAccount::where('account_number', $receiver)->first();

            if (!$toReceive) {
                PaymentLogger::log('Virtual account transfer without identification - user identification needed', [
                    'receiver' => $receiver,
                    'reference' => $reference,
                    'transaction_type' => $data['data']['transaction']['type']
                ]);
                return ['success' => false, 'message' => 'User identification required', 'status_code' => 404];
            }

            $user = $toReceive->user;

        } else {
            $user = User::where('email', $customerEmail)->first();
        }

        if (!$user || !$user->wallet) {
            PaymentLogger::log('User or wallet not found for virtual account funding', [
                'customer_email' => $customerEmail,
                'reference' => $reference
            ]);
            return ['success' => false, 'message' => 'User or wallet not found', 'status_code' => 404];
        }

        $wallet = $user->wallet;
        $oldBalance = $wallet->amount;

        # Create transaction record
        $transaction = TransactionLog::create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'amount' => $amount,
            'amount_before' => $oldBalance,
            'amount_after' => $oldBalance + $amount,
            'currency' => $this->getCurrency($data),
            'description' => 'Received from ' . ($data['data']['customer']['senderName'] ?? 'External source'),
            'status' => 'successful',
            'type' => 'credit',
            'category' => 'external_bank_deposit',
            'service_type' => 'external_bank_deposit',
            'purpose' => 'wallet_funding',
            'payable_type' => 'App\\Models\\Wallet',
            'payable_id' => $wallet->id,
            'provider' => 'nomba',
            'transaction_reference' => $reference,
            'channel' => $this->getPaymentMethod($data),
            'paid_at' => now(),
            'provider_response' => json_encode($data)
        ]);

        $this->createNombaTransaction($transaction, $data);

        $wallet->increment('amount', $amount);

        PaymentLogger::log('Virtual account funded successfully', [
            'transaction_id' => $transaction->id,
            'user_id' => $user->id,
            'amount' => $amount
        ]);

        $sender = $data['data']['customer']['senderName'] ?? $data['data']['customer']['billerId'] ?? 'Someone';

        $this->sendNotifications($user, $transaction, $data, 'virtual_account_funding_completed');

        $this->sendPushNotification(
            $user,
            'Transaction Notification',
            "{$sender} just sent you ₦" . number_format($amount, 2)
        );

        return ['success' => true, 'message' => 'Virtual account funded successfully'];
    }

    /**
     * Process existing transaction based on webhook event
     */
    private function processExistingTransaction(TransactionLog $transaction, array $data): array
    {
        $nombaTransaction = $this->findOrCreateNombaTransaction($transaction, $data);

        return match ($data['event_type']) {
            'payment_success' => $this->handlePaymentSuccess($transaction, $nombaTransaction, $data),
            'payout_success' => $this->handlePayoutSuccess($transaction, $nombaTransaction, $data),
            'payment_failed' => $this->handlePaymentFailed($transaction, $nombaTransaction, $data),
            'payment_reversal' => $this->handlePaymentReversal($transaction, $nombaTransaction, $data),
            'payout_failed' => $this->handlePayoutFailed($transaction, $nombaTransaction, $data),
            'payout_refund' => $this->handlePayoutRefund($transaction, $nombaTransaction, $data),
            default => ['success' => false, 'message' => 'Unhandled event', 'status_code' => 400]
        };
    }

    /**
     * Find or create NombaTransaction record
     */
    private function findOrCreateNombaTransaction(TransactionLog $transaction, array $data): NombaTransaction
    {
        $reference = $this->getTransactionReference($data);
        $transactionId = $data['data']['transaction']['transactionId'];

        $nombaTransaction = NombaTransaction::where('reference', $reference)
            ->orWhere('transaction_id', $transactionId)
            ->first();

        if (!$nombaTransaction) {
            $nombaTransaction = $this->createNombaTransaction($transaction, $data);
        } else {
            $nombaTransaction->update([
                'status' => self::STATUS_MAP[$data['event_type']] ?? 'pending',
                'event_type' => $data['event_type'],
                'metadata' => json_encode($data),
            ]);
        }

        return $nombaTransaction;
    }

    /**
     * Create NombaTransaction record
     */
    private function createNombaTransaction(TransactionLog $transaction, array $data): NombaTransaction
    {
        return NombaTransaction::create([
            'transaction_id' => $data['data']['transaction']['transactionId'],
            'event_type' => $data['event_type'],
            'request_id' => $data['requestId'],
            'reference' => $this->getTransactionReference($data),
            'order_id' => $this->getOrderId($data),
            'merchant_tx_ref' => $data['data']['transaction']['merchantTxRef'] ?? null,
            'amount' => $this->getTransactionAmount($data),
            'fee' => $data['data']['transaction']['fee'] ?? 0,
            'currency' => $this->getCurrency($data),
            'payment_method' => $this->getPaymentMethod($data),
            'channel' => $data['data']['transaction']['originatingFrom'] ?? 'web',
            'transaction_type' => $data['data']['transaction']['type'],
            'status' => self::STATUS_MAP[$data['event_type']] ?? 'pending',
            'paid_at' => isset($data['data']['transaction']['time'])
                ? Carbon::parse($data['data']['transaction']['time']) : now(),
            'card_type' => $data['data']['tokenizedCardData']['cardType'] ?? null,
            'card_last4' => $data['data']['order']['cardLast4Digits']
                ?? substr($data['data']['tokenizedCardData']['cardPan'] ?? '', -4)
                    ?? null,
            'card_issuer' => $data['data']['transaction']['cardIssuer'] ?? null,
            'tokenized_card_data' => isset($data['data']['tokenizedCardData'])
                ? json_encode($data['data']['tokenizedCardData']) : null,
            'wallet_id' => $data['data']['merchant']['walletId'] ?? null,
            'merchant_user_id' => $data['data']['merchant']['userId'] ?? null,
            'wallet_balance' => $data['data']['merchant']['walletBalance'] ?? null,
            'customer_email' => $data['data']['order']['customerEmail'] ?? null,
            'customer_id' => $data['data']['order']['customerId']
                ?? $data['data']['customer']['accountNumber'] ?? null,
            'customer_data' => isset($data['data']['customer'])
                ? json_encode($data['data']['customer']) : null,
            'metadata' => json_encode($data),
            'user_id' => $transaction->user_id,
        ]);
    }

    /**
     * Handle successful payment
     */
    private function handlePaymentSuccess(TransactionLog $transaction, NombaTransaction $nombaTransaction, array $data): array
    {
        $amount = $this->getTransactionAmount($data);

        # Update transaction status
        $transaction->update([
            'status' => 'successful',
            'paid_at' => isset($data['data']['transaction']['time'])
                ? Carbon::parse($data['data']['transaction']['time']) : now(),
            'provider_response' => json_encode($data)
        ]);

        # Update nomba transaction
        $nombaTransaction->update(['status' => 'success', 'request_id' => $data['requestId'] ?? "0"]);

        # Credit wallet if it's a credit transaction
        if ($transaction->wallet && $transaction->type === 'credit') {
            $oldBalance = $transaction->wallet->amount;
            $transaction->wallet->increment('amount', $amount);

            $transaction->update([
                'amount_before' => $oldBalance,
                'amount_after' => $transaction->wallet->fresh()->amount
            ]);

            PaymentLogger::log('Wallet credited from payment success', [
                'wallet_id' => $transaction->wallet->id,
                'amount' => $amount,
                'old_balance' => $oldBalance,
                'new_balance' => $transaction->wallet->fresh()->amount
            ]);
        }

        # Send notifications
        $this->sendNotifications($transaction->user, $transaction, $data, 'wallet_funding_completed');
        $this->sendPushNotification(
            $transaction->user,
            'Transaction Notification',
            "Your account has been credited with ₦" . number_format($amount, 2)
        );

        PaymentLogger::log('Payment success processed', [
            'transaction_id' => $transaction->id,
            'amount' => $amount
        ]);

        return ['success' => true, 'message' => 'Payment success processed'];
    }

    /**
     * Handle failed payment
     */
    private function handlePaymentFailed(TransactionLog $transaction, NombaTransaction $nombaTransaction, array $data): array
    {
        # Update transaction status
        $transaction->update([
            'status' => 'failed',
            'provider_response' => json_encode($data),
            'reason' => $data['data']['transaction']['failureReason'] ?? 'Payment failed'
        ]);

        # Update nomba transaction
        $nombaTransaction->update(['status' => 'failed']);

        # If this was a debit transaction that failed, refund the wallet
        if ($transaction->wallet && $transaction->type === 'debit' && $transaction->status === 'pending') {
            $oldBalance = $transaction->wallet->amount;
            $transaction->wallet->increment('amount', $transaction->amount);

            $this->recordRefundTransaction($transaction, $transaction->wallet, 'payment_failed', $oldBalance, $transaction->wallet->fresh()->amount);
        }

        # Send notifications
        $this->sendNotifications($transaction->user, $transaction, $data, 'payment_failed');
        $this->sendPushNotification(
            $transaction->user,
            'Transaction Failed',
            "Your payment of ₦" . number_format($transaction->amount, 2) . " could not be processed"
        );

        PaymentLogger::log('Payment failed processed', [
            'transaction_id' => $transaction->id,
            'reason' => $data['data']['transaction']['failureReason'] ?? 'Unknown'
        ]);

        return ['success' => true, 'message' => 'Payment failure processed'];
    }

    /**
     * Handle payment reversal
     */
    private function handlePaymentReversal(TransactionLog $transaction, NombaTransaction $nombaTransaction, array $data): array
    {
        $amount = $this->getTransactionAmount($data);

        # Update transaction status
        $transaction->update([
            'status' => 'reversed',
            'provider_response' => json_encode($data),
            'reason' => $data['data']['transaction']['reversalReason'] ?? 'Payment reversed'
        ]);

        # Update nomba transaction
        $nombaTransaction->update(['status' => 'reversed']);

        # Reverse the wallet transaction if it was credited
        if ($transaction->wallet && $transaction->type === 'credit') {
            $oldBalance = $transaction->wallet->amount;

            # Only reverse if wallet has sufficient balance
            if ($oldBalance >= $amount) {
                $transaction->wallet->decrement('amount', $amount);
                $this->recordRefundTransaction($transaction, $transaction->wallet, 'payment_reversal', $oldBalance, $transaction->wallet->fresh()->amount);
            } else {
                PaymentLogger::log('Insufficient balance for reversal', [
                    'transaction_id' => $transaction->id,
                    'wallet_balance' => $oldBalance,
                    'reversal_amount' => $amount
                ]);
            }
        }

        # Send notifications
        $this->sendNotifications($transaction->user, $transaction, $data, 'payment_reversed');
        $this->sendPushNotification(
            $transaction->user,
            'Payment Reversed',
            "Your payment of ₦" . number_format($amount, 2) . " has been reversed"
        );

        PaymentLogger::log('Payment reversal processed', [
            'transaction_id' => $transaction->id,
            'amount' => $amount
        ]);

        return ['success' => true, 'message' => 'Payment reversal processed'];
    }

    /**
     * Handle successful payout
     */
    private function handlePayoutSuccess(TransactionLog $transaction, NombaTransaction $nombaTransaction, array $data): array
    {
        # Update transaction and nomba transaction status
        $transaction->update([
            'status' => 'successful',
            'paid_at' => isset($data['data']['transaction']['time'])
                ? Carbon::parse($data['data']['transaction']['time']) : now(),
            'provider_response' => json_encode($data)
        ]);

        $nombaTransaction->update(['status' => 'success']);

        # Send notifications
        $this->sendNotifications($transaction->user, $transaction, $data, 'external_bank_transfer_completed');

        $amount = number_format($transaction->amount, 2);
        $recipient = $data['data']['customer']['recipientName'] ?? 'recipient';
        $this->sendPushNotification(
            $transaction->user,
            'Transaction Notification',
            "₦{$amount} successfully sent to {$recipient}"
        );

        PaymentLogger::log('Payout success processed', [
            'transaction_id' => $transaction->id,
            'amount' => $transaction->amount
        ]);

        return ['success' => true, 'message' => 'Payout success processed'];
    }

    /**
     * Handle failed payout
     */
    private function handlePayoutFailed(TransactionLog $transaction, NombaTransaction $nombaTransaction, array $data): array
    {
        # Update transaction status
        $transaction->update([
            'status' => 'failed',
            'provider_response' => json_encode($data),
            'reason' => $data['data']['transaction']['failureReason'] ?? 'Payout failed'
        ]);

        # Update nomba transaction
        $nombaTransaction->update(['status' => 'failed']);

        # Refund wallet since payout failed
        if ($transaction->wallet && $transaction->type === 'debit') {
            $oldBalance = $transaction->wallet->amount;
            $transaction->wallet->increment('amount', $transaction->amount);

            $this->recordRefundTransaction($transaction, $transaction->wallet, 'payout_failed', $oldBalance, $transaction->wallet->fresh()->amount);
        }

        # Send notifications
        $this->sendNotifications($transaction->user, $transaction, $data, 'payout_failed');
        $this->sendPushNotification(
            $transaction->user,
            'Transfer Failed',
            "Your transfer of ₦" . number_format($transaction->amount, 2) . " could not be processed. Amount has been refunded."
        );

        PaymentLogger::log('Payout failed processed', [
            'transaction_id' => $transaction->id,
            'reason' => $data['data']['transaction']['failureReason'] ?? 'Unknown'
        ]);

        return ['success' => true, 'message' => 'Payout failure processed'];
    }

    /**
     * Handle payout refund
     */
    private function handlePayoutRefund(TransactionLog $transaction, NombaTransaction $nombaTransaction, array $data): array
    {
        $amount = $this->getTransactionAmount($data);

        # Update transaction status
        $transaction->update([
            'status' => 'refunded',
            'provider_response' => json_encode($data),
            'reason' => $data['data']['transaction']['refundReason'] ?? 'Payout refunded'
        ]);

        # Update nomba transaction
        $nombaTransaction->update(['status' => 'refunded']);

        # Refund to wallet
        if ($transaction->wallet && $transaction->type === 'debit') {
            $oldBalance = $transaction->wallet->amount;
            $transaction->wallet->increment('amount', $amount);

            $this->recordRefundTransaction($transaction, $transaction->wallet, 'payout_refund', $oldBalance, $transaction->wallet->fresh()->amount);
        }

        # Send notifications
        $this->sendNotifications($transaction->user, $transaction, $data, 'payout_refunded');
        $this->sendPushNotification(
            $transaction->user,
            'Transfer Refunded',
            "Your transfer of ₦" . number_format($amount, 2) . " has been refunded to your wallet"
        );

        PaymentLogger::log('Payout refund processed', [
            'transaction_id' => $transaction->id,
            'amount' => $amount
        ]);

        return ['success' => true, 'message' => 'Payout refund processed'];
    }

    /**
     * Record refund transaction
     */
    private function recordRefundTransaction(TransactionLog $originalTransaction, $wallet, string $serviceType, float $oldBalance, float $newBalance): void
    {
        $reference = Utility::txRef("reverse", "system");

        $description = match ($serviceType) {
            'payment_failed' => 'failed payment refund',
            'payment_reversal' => 'reversed payment refund',
            'payout_failed' => 'failed transfer refund',
            'payout_refund' => 'payout refund',
            'transfer_failed' => 'failed transfer refund',
            'transfer_reversed' => 'reversed transfer refund',
            default => 'transfer refund',
        };

        TransactionLog::create([
            'user_id' => $originalTransaction->user->id,
            'wallet_id' => $wallet->id,
            'type' => 'credit',
            'amount' => $originalTransaction->amount,
            'category' => 'refund',
            'transaction_reference' => $reference,
            'service_type' => $serviceType,
            'amount_before' => $oldBalance,
            'amount_after' => $newBalance,
            'status' => 'successful',
            'provider' => 'system',
            'channel' => 'internal',
            'currency' => 'NGN',
            'description' => $description,
            'payload' => json_encode([
                'source' => 'webhook_refund',
                'original_transaction_id' => $originalTransaction->id,
                'provider' => 'system',
                'channel' => 'internal',
            ])
        ]);
    }

    /**
     * Send notifications (push and in-app)
     */
    private function sendNotifications($user, TransactionLog $transaction, array $data, string $eventType): void
    {
        if (!$user) return;

        try {
            # Send in-app notification based on event type
            match ($eventType) {
                'wallet_funding_completed' => $user->notify(new NombaFundedWallet($transaction, $data)),
                'virtual_account_funding_completed' => $user->notify(new NombaVirtualAccountDepositNotification($transaction, $data)),
                'external_bank_transfer_completed' => $user->notify(new NombaTransferSuccessful($transaction, $data)),
                'payment_failed' => $user->notify(new NombaPaymentFailed($transaction, $data)),
                'payment_reversed' => $user->notify(new NombaPaymentReversed($transaction, $data)),
                'payout_failed' => $user->notify(new NombaPayoutFailed($transaction, $data)),
                'payout_refunded' => $user->notify(new NombaPayoutRefunded($transaction, $data)),
                default => null
            };

            # Track the event
            $this->trackWebhookEvent($eventType, $transaction, $data);
        } catch (\Exception $e) {
            PaymentLogger::error('Notification sending failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'event_type' => $eventType
            ]);
        }
    }

}

