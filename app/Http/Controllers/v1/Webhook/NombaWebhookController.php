<?php

namespace App\Http\Controllers\v1\Webhook;

use App\Events\PushNotificationEvent;
use App\Helpers\PaymentLogger;
use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\NombaTransaction;
use App\Models\PlatformFee;
use App\Models\TransactionFee;
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



class NombaWebhookController extends Controller
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
            'payment_success' => $data['data']['order']['orderReference']
                ?? $data['data']['transaction']['merchantTxRef']
                ?? $data['data']['transaction']['transactionId'] ?? null,
            'payout_success' => $data['data']['transaction']['merchantTxRef'] ?? null,
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
            'payment_success' => $data['data']['order']['orderId'] ?? null,
            'payout_success', 'payout_refund' => $data['data']['transaction']['transactionId'] ?? null,
            default => $data['data']['order']['orderId']
                ?? $data['data']['transaction']['transactionId']
                ?? $data['data']['id']
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

        return $data['event_type'] === 'payout_success' ? 'bank_transfer' : null;
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

        # Check for idempotency
        if ($this->isAlreadyProcessed($reference)) {
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
     * Check if webhook has already been processed
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



        $feeCalculation = $this->calculateTransactionFee('nomba', 'deposit', $amount);

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
            $user = User::where('email', $user->email)->first();
        }

        if (!$user || !$user->wallet) {
            PaymentLogger::log('User or wallet not found for virtual account funding', [
                'customer_email' => $customerEmail,
                'reference' => $reference
            ]);
            return ['success' => false, 'message' => 'User or wallet not found', 'status_code' => 404];
        }

        # Check wallet tier limit before processing (for logging purposes only)
        $tierCheck = $this->checkWalletTierLimit($user, $amount);

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
            'image' => Utility::getBankLogoByCode($data['data']['customer']['bankCode']),
            'transaction_reference' => $reference,
            'channel' => $this->getPaymentMethod($data),
            'paid_at' => now(),
            'provider_response' => json_encode([
                'data' => $data,
                'tier_check_result' => $tierCheck
            ])
        ]);

        $this->createNombaTransaction($transaction, $data);

        $wallet->increment('amount', $amount);

        $postCreditTierCheck = $this->checkWalletTierLimit($user, 0);

        PaymentLogger::log('Virtual account funded successfully', [
            'transaction_id' => $transaction->id,
            'user_id' => $user->id,
            'amount' => $amount,
            'pre_credit_tier_check' => $tierCheck,
            'post_credit_tier_check' => $postCreditTierCheck
        ]);

        $feeTransaction = $this->processFeeTransaction($transaction, $feeCalculation, 'nomba');

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
     * Handle successful payment
     */
    private function handlePaymentSuccess(TransactionLog $transaction, NombaTransaction $nombaTransaction, array $data): array
    {
        $amount = $this->getTransactionAmount($data);

        $tierCheck = $this->checkWalletTierLimit($transaction->user, $amount);

        # Update transaction status
        $transaction->update([
            'status' => 'successful',
            'paid_at' => isset($data['data']['transaction']['time'])
                ? Carbon::parse($data['data']['transaction']['time']) : now(),
            'provider_response' => json_encode($data)
        ]);

        # Update nomba transaction
        $nombaTransaction->update(['status' => 'success', 'request_id' => $data['data']['requestId'] ??  "0"]);

        $feeCalculation = $this->calculateTransactionFee('nomba', 'deposit', $amount);

        # Credit wallet if it's a credit transaction
        if ($transaction->wallet && $transaction->type === 'credit') {
            $oldBalance = $transaction->wallet->amount;
            $transaction->wallet->increment('amount', $amount);

            $postCreditTierCheck = $this->checkWalletTierLimit($transaction->user, 0);


            $transaction->update([
                'amount_before' => $oldBalance,
                'amount_after' => $transaction->wallet->fresh()->amount
            ]);

            PaymentLogger::log('Wallet credited from payment success', [
                'wallet_id' => $transaction->wallet->id,
                'amount' => $amount,
                'old_balance' => $oldBalance,
                'new_balance' => $transaction->wallet->fresh()->amount,
                'pre_credit_tier_check' => $tierCheck,
                'post_credit_tier_check' => $postCreditTierCheck
            ]);
        }

        $feeTransaction = $this->processFeeTransaction($transaction, $feeCalculation, 'nomba');

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
            PaymentLogger::error('Nomba webhook Notification sending failed', [
                'error' => Utility::getExceptionDetails($e)
            ]);
        }
    }

    /**
     * Send push notification safely
     */
    private function sendPushNotification($user, string $title, string $message): void
    {
        if (!$user) return;

        try {
            event(new PushNotificationEvent($user, $title, $message));
            PaymentLogger::log("Push notification sent", ['user_id' => $user->id]);
        } catch (\Throwable $e) {
            PaymentLogger::error("Push notification failed", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Track webhook events
     */
    private function trackWebhookEvent(string $eventType, TransactionLog $transaction, array $webhookData): void
    {
        try {
            $trackingData = [
                'user_id' => $transaction->user_id,
                'transaction_id' => $transaction->id,
                'amount' => $transaction->amount,
                'provider' => 'nomba',
                'reference' => $this->getTransactionReference($webhookData),
                'status' => $transaction->status,
                'webhook_event' => $webhookData['event_type'],
                'processed_at' => now()->toISOString(),
            ];

            $description = $this->getEventDescription($eventType, $transaction, $webhookData);
            $this->tracker->track($eventType, $description, $trackingData);
        } catch (\Exception $e) {
            PaymentLogger::error('Event tracking failed', [
                'error' => $e->getMessage(),
                'transaction_id' => $transaction->id,
                'event_type' => $eventType,
            ]);
        }
    }

    /**
     * Get event description for tracking
     */
    private function getEventDescription(string $eventType, TransactionLog $transaction, array $webhookData): string
    {
        $amount = number_format($transaction->amount, 2);

        return match ($eventType) {
            'wallet_funding_completed' => "Wallet deposit of ₦{$amount} completed successfully",
            'virtual_account_funding_completed' => "Received ₦{$amount} via virtual account",
            'external_bank_transfer_completed' => "Bank transfer of ₦{$amount} completed successfully",
            default => "Transaction of ₦{$amount} processed"
        };
    }
    /**
     * Create completed fee transaction record (reusable for all providers)
     */
    private function processFeeTransaction($transaction, $feeCalculation, $provider)
    {
        if (!$feeCalculation['success'] || $feeCalculation['fee'] <= 0) {
            return null;
        }

        $wallet = $transaction->user->wallet;

        # Actually deduct the fee from wallet
        $feeOldBalance = $wallet->fresh()->amount;
        $wallet->decrement('amount', $feeCalculation['fee']);
        $feeNewBalance = $wallet->fresh()->amount;

        $feeTransaction = $this->createCompletedFeeTransaction(
            $transaction->user,
            $transaction,
            $feeCalculation['fee'],
            $transaction->transaction_reference,
            $feeCalculation,
            $provider,
            $feeOldBalance,
            $feeNewBalance
        );

        PaymentLogger::log('Fee deducted and transaction created', [
            'fee_transaction_id' => $feeTransaction->id,
            'parent_transaction_id' => $transaction->id,
            'fee_amount' => $feeCalculation['fee'],
            'fee_percentage' => $feeCalculation['fee_percentage'],
            'wallet_balance_before_fee' => $feeOldBalance,
            'wallet_balance_after_fee' => $feeNewBalance
        ]);

        return $feeTransaction;
    }

    /**
     * Create completed fee transaction record (reusable for all providers)
     */
    private function createCompletedFeeTransaction($user, $mainTransaction, $feeAmount, $reference, $feeCalculation, $provider, $balanceBefore, $balanceAfter)
    {
        $feeTransaction =  TransactionLog::create([
            'user_id' => $user->id,
            'wallet_id' => $user->wallet->id,
            'type' => 'debit', # Fee is a debit from user's perspective
            'amount' => $feeAmount,
            'category' => 'charges',
            'transaction_reference' => $reference,
            'service_type' => 'transaction_fee',
            'amount_before' => $balanceBefore, // Balance before fee deduction
            'amount_after' => $balanceAfter,   // Balance after fee deduction
            'status' => 'successful', # Created as successful since payment confirmed
            'provider' => $provider,
            'channel' => $provider ,
            'currency' => "NGN",
            'description' => "Charges",
            'paid_at' => now(),
            'payload' => json_encode([
                'parent_transaction_id' => $mainTransaction->id,
                'fee_calculation' => $feeCalculation,
                'processed_at' => now(),
            ]),
        ]);

        # Also create record in PlatformFees table for better analytics
        PlatformFee::create([
            'transaction_id' => $mainTransaction->id,
            'user_id' => $user->id,
            'fee_amount' => $feeAmount,
            'fee_percentage' => $feeCalculation['fee_percentage'] ?? 0,
            'provider' => $provider,
            'transaction_type' => $this->getTransactionType($mainTransaction), // deposit, transfer, etc.
        ]);

        return $feeTransaction;
    }



    /**
     * Get transaction type for platform fees
     */
    private function getTransactionType($transaction): string
    {
        // Map transaction categories to platform fee types
        $typeMapping = [
            'deposit' => 'deposit',
            'transfer' => 'transfer',
            'withdrawal' => 'withdrawal',
            'payment' => 'payment',
        ];

        return $typeMapping[$transaction->category] ?? 'other';
    }


    /**
     * Calculate transaction fee based on provider, type, and amount (reusable)
     */
    private function calculateTransactionFee($provider, $type, $amount): array
    {
        try {
            $feeRule = TransactionFee::where('provider', $provider)
                ->where('type', $type)
                ->where('min', '<=', $amount)
                ->where('max', '>=', $amount)
                ->first();

            if (!$feeRule) {
                // No fee structure found - continue with zero fee
                return [
                    'success' => true,
                    'message' => "No fee structure configured for {$provider} {$type} - proceeding with zero fee",
                    'fee' => 0,
                    'fee_percentage' => 0,
                    'fee_rule_id' => null,
                ];
            }

            $fee = ($amount * $feeRule->fee) / 100; // Assuming fee is percentage

            return [
                'success' => true,
                'message' => 'Fee calculated successfully',
                'fee' => round($fee, 2),
                'fee_percentage' => $feeRule->fee,
                'fee_rule_id' => $feeRule->id,
                'amount_range' => [
                    'min' => $feeRule->min,
                    'max' => $feeRule->max,
                ],
            ];
        } catch (\Exception $e) {
            # Even on error, continue with zero fee instead of stopping transaction
            PaymentLogger::error('Fee calculation error - proceeding with zero fee', [
                'provider' => $provider,
                'type' => $type,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => true,
                'message' => 'Fee calculation failed - proceeding with zero fee',
                'fee' => 0,
                'fee_percentage' => 0,
                'fee_rule_id' => null,
            ];
        }
    }


    /**
     * Check if user's wallet balance exceeds their tier limit and lock wallet if necessary
     *
     * @param User $user
     * @param float $newAmount The new amount that will be added to wallet
     * @return array
     */
    private function checkWalletTierLimit($user, $newAmount = 0)
    {
        try {
            #  Get user's wallet
            $wallet = $user->wallet;
            if (!$wallet) {
                return [
                    'success' => false,
                    'message' => 'User wallet not found',
                    'wallet_locked' => false
                ];
            }

            #  Get user's tier
            $tier = $user->tier;
            if (!$tier) {
                return [
                    'success' => false,
                    'message' => 'User tier not found',
                    'wallet_locked' => false
                ];
            }

            #  Calculate potential new wallet balance
            $currentBalance = (float) $wallet->amount;
            $potentialBalance = $currentBalance + (float) $newAmount;
            $tierWalletLimit = $tier->wallet_balance !== null ? (float) $tier->wallet_balance : null;

            #  If tier has no wallet limit set, allow transaction
            if (is_null($tierWalletLimit)) {
                return [
                    'success' => true,
                    'message' => 'No wallet limit set for tier',
                    'wallet_locked' => false,
                    'current_balance' => $currentBalance,
                    'potential_balance' => $potentialBalance,
                    'tier_limit' => null
                ];
            }

            #  Check if potential balance exceeds tier limit
            if ($potentialBalance > $tierWalletLimit) {
                #  Lock the wallet
                $wallet->update([
                    'status' => 'locked',
                    'has_exceeded_limit' => true
                ]);

                PaymentLogger::log('Wallet locked due to tier limit exceeded', [
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                    'tier_name' => $tier->name,
                    'tier_limit' => $tierWalletLimit,
                    'current_balance' => $currentBalance,
                    'potential_balance' => $potentialBalance,
                    'excess_amount' => $potentialBalance - $tierWalletLimit
                ]);

                return [
                    'success' => false,
                    'message' => "Wallet locked: Balance exceeds {$tier->name} tier limit of ₦" . number_format($tierWalletLimit, 2),
                    'wallet_locked' => true,
                    'current_balance' => $currentBalance,
                    'potential_balance' => $potentialBalance,
                    'tier_limit' => $tierWalletLimit,
                    'excess_amount' => $potentialBalance - $tierWalletLimit
                ];
            }

            #  Balance is within tier limit
            return [
                'success' => true,
                'message' => 'Wallet balance within tier limit',
                'wallet_locked' => false,
                'current_balance' => $currentBalance,
                'potential_balance' => $potentialBalance,
                'tier_limit' => $tierWalletLimit,
                'remaining_limit' => $tierWalletLimit - $potentialBalance
            ];

        } catch (\Exception $e) {
            PaymentLogger::log('Error checking wallet tier limit', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error checking wallet tier limit: ' . $e->getMessage(),
                'wallet_locked' => false
            ];
        }
    }

}



