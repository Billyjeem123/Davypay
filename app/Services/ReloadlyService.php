<?php

namespace App\Services;

use App\Events\PushNotificationEvent;
use App\Helpers\BillLogger;
use App\Helpers\Utility;
use App\Http\Controllers\v1\Transaction\TransactionController;
use App\Models\TransactionLog;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\JsonResponse;

class ReloadlyService
{
    private $authUrl;
    private $giftcardBaseUrl;
    private $clientId;
    private $clientSecret;
    private $accessToken;

    public function __construct()
    {
        $this->clientId = env('RELOADLY_CLIENT_ID');
        $this->clientSecret = env('RELOADLY_CLIENT_SECRET');
        $this->authUrl = 'https://auth.reloadly.com/oauth/token';
//        $this->giftcardBaseUrl = env('APP_ENV') === 'local'
//            ? 'https://giftcards-sandbox.reloadly.com'
//            : 'https://giftcards.reloadly.com';

        $this->giftcardBaseUrl = env('APP_ENV') === 'local'
            ? 'https://giftcards-sandbox.reloadly.com'
            : 'https://giftcards-sandbox.reloadly.com';
        $this->accessToken = null;
    }

    private function makeCurlRequest($url, $method = 'GET', $headers = [], $body = null)
    {
        $ch = curl_init();

        $defaultHeaders = [
            'Content-Type: application/json'
        ];
        $finalHeaders = array_merge($defaultHeaders, $headers);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            }
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $finalHeaders);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception("cURL Error: $error");
        }

        curl_close($ch);

        if ($httpcode >= 400) {
            throw new \Exception("HTTP Error ($httpcode): $response");
        }

        return json_decode($response, true);
    }

    private function getAuthToken(): string
    {
        try {
            $body = [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'client_credentials',
                'audience' => $this->giftcardBaseUrl
            ];

            $response = $this->makeCurlRequest($this->authUrl, 'POST', [], $body);
            $this->accessToken = $response['access_token'] ?? '';
            return $this->accessToken;
        } catch (\Exception $e) {
            return '';
        }
    }

    public function buyGiftcard($data):JsonResponse
    {
        try {
            if (!$this->accessToken) $this->getAuthToken();


            $url = "{$this->giftcardBaseUrl}/orders";
            $headers = [
                "Authorization: Bearer {$this->accessToken}"
            ];
            $body = [
                'unitPrice' => $data['amount'],
                'quantity' => $data['quantity'],
                'useLocalAmount' => true,
                'productId' => $data['product_id'],
                'countryCode' => $data['recipient_country_code'],
                'recipientPhoneDetails' => [
                    'countryCode' => $data['recipient_country_code'],
                    'phoneNumber' => $data['recipient_phone']
                ]
            ];

            $response = $this->makeCurlRequest($url, 'POST', $headers, $body);
            return self::process_response($response, $data);

        } catch (\Exception $e) {
            TransactionLog::update_info($data['transaction_id'],['status' =>  'failed', 'provider_response' => $e->getMessage(), 'amount_after' => $data['amount_after'] + $data['amount_giftcard']]);
            Wallet::add_to_wallet($data['amount']);
            return response()->json([
                'status' => false,
                'message' => 'Transaction Failed',
            ]);

        }
    }

    private static function process_response($response, $data):JsonResponse
    {
        $response_array = is_array($response) ? $response :  json_decode($response, true);
        if ($response_array['status'] == 'SUCCESSFUL') {
            TransactionLog::update_info($data['transaction_id'],['status' =>  'successful', 'provider_response' => $response]);

            return response()->json([
                'status' => true,
                'message' => "You have successfully purchased giftcard"
            ]);
        }else{
            TransactionLog::update_info($data['transaction_id'],['status' =>  'failed', 'provider_response' => $response, 'amount_after' => $data['amount_after'] + $data['amount_giftcard']]);
            Wallet::add_to_wallet($data['amount']);
            return response()->json([
                'status' => false,
                'message' => $response_array['response_description']
            ]);
        }
    }


    public function giftcardFxRate($data)
    {
        try {
            if (!$this->accessToken) $this->getAuthToken();

            $url = "{$this->giftcardBaseUrl}/fx-rate?currencyCode={$data['currencyCode']}&amount={$data['amount']}";
            $headers = ["Authorization: Bearer {$this->accessToken}"];

            $res = $this->makeCurlRequest($url, 'GET', $headers);

            return [
                'sender_currency' => $res['senderCurrency'],
                'sender_amount' => $res['senderAmount'],
                'recipient_currency' => $res['recipientCurrency'],
                'recipient_amount' => $res['recipientAmount']
            ];

        } catch (\Exception $e) {
        }
    }

    public function getGiftcardList(): JsonResponse
    {
        try {
            if (!$this->accessToken) $this->getAuthToken();

            $url = "{$this->giftcardBaseUrl}/products";
            $headers = ["Authorization: Bearer {$this->accessToken}"];

            $data = $this->makeCurlRequest($url, 'GET', $headers);

            return response()->json([
                'status' => true,
                'message' => "Gift cards retrieved successfully",
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => "Gift cards could not be retrieved",
                'data' => Utility::getExceptionDetails($e)
            ]);
        }
    }


    private static function handleFailedTransaction($transactionId, $data): void
    {
        $transaction = TransactionLog::with(['user', 'wallet'])->find($transactionId);
        if (!$transaction) {
            BillLogger::error('Transaction not found for reversal', [
                'transaction_id' => $transactionId,
                'data' => $data
            ]);
            return;
        }
        DB::transaction(function () use ($transaction, $data) {
            $reversalAmount = floatval($data['amount']);
            $wallet = $transaction->wallet;
            $user = $transaction->user;

            if (!$wallet || !$user) {
                BillLogger::error('Wallet or User not found for failed transaction reversal', [
                    'transaction_id' => $transaction->id
                ]);
                return;
            }

            $oldBalance = $wallet->amount;

            $transaction->update(['status' => 'failed']);

            if ($reversalAmount > 0) {
                self::creditUserWallet($user, $reversalAmount, $transaction);
            }

            $newBalance = $wallet->fresh()->amount;

            $referenceId = Utility::txRef("reverse", "system", false);

            TransactionLog::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'type' => 'credit',
                'category' => 'refund',
                'amount' => $reversalAmount,
                'transaction_reference' => $referenceId,
                'service_type' => $transaction->service_type,
                'amount_before' => $oldBalance,
                'amount_after' => $newBalance,
                'status' => 'successful',
                'provider' => 'system',
                'channel' => 'internal',
                'currency' => 'NGN',
                'description' => "Refund for payment: " . ($data['product_code'] ?? 'Unknown'),
                'provider_response' => json_encode([
                    'transfer_type' => 'in_app',
                    'data' => $data,
                ]),
                'payload' => json_encode([
                    'refund_status' => "failed",
                    'provider' => "vtpass"
                ]),
            ]);

            BillLogger::log('Transaction reversed', [
                'requestId' => $transaction->request_id,
                'amount' => $reversalAmount,
            ]);
        });

        self::trackBillPaymentEvent('bill_payment_reversed', $data);

        $user = $transaction->user ?? null;
        if ($user) {
            self::sendSafePushNotification(
                $user,
                'Transaction Notification',
                "Payment for " . ($data['content']['transactions']['product_name'] ?? '_') . " has been reversed."
            );
        }
    }


    private static function sendSafePushNotification($user, string $title, string $message): void
    {
        try {
            event(new PushNotificationEvent($user, $title, $message));
        } catch (\Throwable $e) {
            BillLogger::error("Push notification event failed", [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private static function creditUserWallet($user, $amount, $transaction): void
    {
        $wallet = $transaction->wallet;
        $wallet->increment('amount', $amount);

        BillLogger::log('User wallet credited', [
            'user_id' => $user->id,
            'amount' => $amount,
            'transaction_id' => $transaction->id
        ]);
    }


}
