<?php

namespace App\Services;

use App\Exceptions\StroUsdtException;
use App\Models\DeliveryFee;
use App\Models\NairaCard;
use App\Models\TransactionFee;
use App\Models\TransactionLog;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class CardService
{

    protected string $publicKey;
    protected string $mode;

    public function __construct()
    {
        $this->publicKey = config('services.strowallet.public_key');
        $this->mode = config('services.strowallet.mode', 'sandbox');
    }

    /**
     * Fetch physical card details from Strowallet.
     * @throws StroUsdtException
     */
    public function viewCard(string $cardId): array
    {
        $payload = [
            'public_key' => $this->publicKey,
            'card_id'    => $cardId,
            'mode'       => $this->mode,
        ];

        $response = app(StroUsdtService::class)->request('get', "/naira_viewcard/", $payload, false);

        if (!($response['success'] ?? false)) {
            throw new StroUsdtException($response['message'] ?? 'Failed to fetch card details', 400, $response);
        }

        $apiData = $response['data'] ?? [];

        $localCard = NairaCard::where('card_id', $cardId)->first();

        return [
            'card_id'          => $cardId,
            'delivery_status'  => $localCard?->card_status ?? 'unknown',
            'card_details'     => $apiData,
        ];
    }

    /**
     * Create Naira Card User in Strowallet
     * @throws StroUsdtException
     */
    public function createNairaCardUser($user): array
    {
        $user->loadMissing('kyc');

        if (!$user->kyc->dob) {
            throw new StroUsdtException('Date of birth is required to create a Naira card');
        }

        if (!$user->kyc->nin) {
            throw new StroUsdtException('NIN is required to create a Naira card');
        }

        $dobFormatted = str_replace('-', '/', $user->kyc->dob);
        $payload = [
            'public_key' => $this->publicKey,
            'firstname' => $user->first_name,
            'lastname' => $user->last_name,
            'email' => $user->email,
            'phone' => $this->validatePhoneNumber($user->phone),
            'nin' => $user->kyc->nin,
            'dob' => $dobFormatted,
            'name' => $user->username,
            'line1' => $user->kyc->address,
            'state' => 'lg',
            'city' => 'Ikeja',
            'zipCode' => "100001",
            'mode' => $this->mode,
        ];

        $response = app(StroUsdtService::class)->request('post', "/naira_carduser/", $payload, false);

        if (!($response['success'] ?? false)) {
            throw new StroUsdtException($response['message'] ?? 'Card user creation failed', 400, $response);
        }

        return NairaCard::updateOrCreate(
            ['user_id' => $user->id],
            [
                'firstname' => $user->first_name,
                'lastname' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'nin' => $user->kyc->nin,
                'dob' => $dobFormatted,
                'name' => $user->username,
                'line' => $user->kyc->address,
                'city' => $user->city,
                'state' => $user->state,
                'customer_id' => $response['data']['customer_id'],
                'status' => 'created_user',
                'api_response' => $response,
            ]
        )->toArray();
    }

    /**
     * Create Naira Card via Strowallet
     */
    public function createNairaCard($user, string $type, string $brand, array $address = [], ?string $number = null): array
    {
        return DB::transaction(function () use ($user, $type, $brand, $address, $number) {

            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();

            if (!$wallet) {
                throw new StroUsdtException('Wallet not found for user');
            }

            $fee = TransactionFee::where('type', $type === 'physical' ? 'physical_card' : 'virtual_card')
                ->value('min');

            if (!$fee) {
                throw new StroUsdtException('Permit fee not configured');
            }

            if ($wallet->amount < $fee) {
                throw new StroUsdtException('Insufficient NGN balance in wallet');
            }

            $before = $wallet->amount;
            $wallet->decrement('amount', $fee);
            $after = $wallet->amount;

            $nairaCard = NairaCard::where('user_id', $user->id)->firstOrFail();

            $payload = [
                'public_key'  => $this->publicKey,
                'customerId'  => $nairaCard->customer_id,
                'type'        => $type,
                'brand'       => $brand,
                'mode'        => $this->mode,
            ];

            if ($type === 'physical') {
                if ($number) {
                    $payload['number'] = $number;
                }

                $payload['address']   = $address['address'] ?? null;
                $payload['city']      = $address['city'] ?? null;
                $payload['state']     = $address['state'] ?? null;
                $payload['postcode']  = $address['postcode'] ?? null;
                $payload['country']   = $address['country'] ?? 'Nigeria';
                $payload['phone']     = $address['phone'] ?? null;
                $payload['house_no']  = $address['house_no'] ?? null;
                $payload['bus_stop']  = $address['nearest_bus_stop'] ?? null;
            }

            $response = app(StroUsdtService::class)->request('post', "/naira_createcard/", $payload, false);

            if (!($response['success'] ?? false)) {
                $wallet->increment('amount', $fee);
                throw new StroUsdtException($response['message'] ?? 'Card creation failed', 400, $response);
            }

            $data = $response['data'] ?? [];

            $nairaCard->update([
                'number'          => $payload['number'] ?? null,
                'card_id'         => $data['card_id'] ?? null,
                'brand'           => $data['brand'] ?? $brand,
                'type'            => $data['type'] ?? $type,
                'mask'            => $data['maskedPan'] ?? null,
                'expiration'      => ($data['expiryMonth'] ?? '') . '/' . ($data['expiryYear'] ?? ''),
                'status'          => $data['status'] ?? 'active',
                'api_response'    => $response,
                'line'            => $address['address'] ?? null,
                'city'            => $address['city'] ?? null,
                'state'           => $address['state'] ?? null,
                'postcode'        => $address['postcode'] ?? null,
                'country'         => $address['country'] ?? 'Nigeria',
                'phone'           => $address['phone'] ?? null,
                'house_no'        => $address['house_no'] ?? null,
                'nearest_bus_stop'=> $address['nearest_bus_stop'] ?? null,
            ]);

            TransactionLog::create_transaction([
                'service_type'  => 'create_naira_card',
                'amount'        => $fee,
                'amount_before' => $before,
                'amount_after'  => $after,
                'status'        => 'success',
                'wallet_id'     => $wallet->id,
                'provider'      => 'Strowallet',
                'type'          => 'debit',
                'description'   => "Created {$type} Naira Card ({$brand})",
            ]);

            return [
                'success' => true,
                'message' => 'Naira Card created successfully',
                'data'    => $data,
            ];
        });
    }



    private function validatePhoneNumber(?string $phone): string
    {
        if (!$phone) {
            throw new StroUsdtException('Phone number is required');
        }

        $phone = preg_replace('/\D/', '', $phone);

        if (strlen($phone) === 11 && $phone[0] === '0') {
            return '234' . substr($phone, 1);
        } elseif (strlen($phone) === 10) {
            return '234' . $phone;
        } elseif (strlen($phone) === 13 && str_starts_with($phone, '234')) {
            return $phone;
        } elseif (strlen($phone) >= 13) {
            return $phone;
        }
        throw new StroUsdtException("Invalid phone number format. Phone must be a valid Nigerian number. Received: {$phone}");
    }

    /**
     * Fetch physical card details from Strowallet.
     * @throws StroUsdtException
     */
    public function viewCardHistory(string $cardId): array
    {
        $payload = [
            'public_key' => $this->publicKey,
            'card_id' => $cardId,
            'mode' => $this->mode,
        ];

        $response = app(StroUsdtService::class)->request('get', "/naira_cardhistory/", $payload, false);

        if (!($response['success'] ?? false)) {
            throw new StroUsdtException($response['message'] ?? 'Failed to fetch card details', 400, $response);
        }

        return $response['data'] ?? [];
    }

    /**
     * @throws StroUsdtException
     */
    public function changePin(string $cardId, string $oldPin, string $newPin): array
    {
        $payload = [
            'public_key' => $this->publicKey,
            'card_id' => $cardId,
            'old_pin' => $oldPin,
            'new_pin' => $newPin,
            'mode' => $this->mode,
        ];

        $response = app(StroUsdtService::class)->request('post', "/naira_changepin/", $payload, false);

        if (!($response['success'] ?? false)) {
            throw new StroUsdtException($response['message'] ?? 'Failed to change PIN', 400, $response);
        }

        return $response['data'] ?? [];
    }

    /**
     * @throws StroUsdtException
     */
    public function resetPin(string $cardId, string $newPin): array
    {
        $payload = [
            'public_key' => $this->publicKey,
            'card_id' => $cardId,
            'new_pin' => $newPin,
            'mode' => $this->mode,
        ];

        $response = app(StroUsdtService::class)->request('put', "/naira_resetPin/", $payload, false);

        if (!($response['success'] ?? false)) {
            throw new StroUsdtException($response['message'] ?? 'Failed to change PIN', 400, $response);
        }

        return $response['data'] ?? [];
    }

    /**
     * Enable 2fa physical card.
     * @throws StroUsdtException
     */
    public function enable2fa(string $cardId): array
    {
        $payload = [
            'public_key' => $this->publicKey,
            'card_id' => $cardId,
            'mode' => $this->mode,
        ];

        $response = app(StroUsdtService::class)->request('post', "/naira_enable2Fa/", $payload, false);

        if (!($response['success'] ?? false)) {
            throw new StroUsdtException($response['message'] ?? 'Failed to fetch card details', 400, $response);
        }

        return $response['data'] ?? [];
    }

    /**
     * Create Dispute for physical card.
     * @throws StroUsdtException
     */
    /**
     * Create Dispute for physical card.
     * @throws StroUsdtException
     */
    public function createDispute(string $reason, string $explanation, string $transactionId): array
    {
        $payload = [
            'public_key' => $this->publicKey,
            'reason' => $reason,
            'explanation' => $explanation,
            'transactionId' => $transactionId,
            'mode' => $this->mode,
        ];

        $response = app(StroUsdtService::class)->request('post', "/naira_CreateDispute/", $payload, false);

        if (!($response['success'] ?? false)) {
            throw new StroUsdtException($response['message'] ?? 'Failed to create dispute', 400, $response);
        }

        return $response['data'] ?? [];
    }

    /**
     * Update card status (activate/deactivate)
     * @throws StroUsdtException
     */
    public function updateCardStatus(string $cardId, string $status): array
    {
        $payload = [
            'public_key' => $this->publicKey,
            'card_id' => $cardId,
            'status' => $status,
            'mode' => $this->mode,
        ];

        $response = app(StroUsdtService::class)->request('put', "/naira_ChangeStatus/", $payload, false);

        if (!($response['success'] ?? false)) {
            throw new StroUsdtException($response['message'] ?? 'Failed to update card status', 400, $response);
        }

        return $response['data'] ?? [];
    }

    public function getTransactionFees(string $type = 'physical_card', string $state = 'Lagos'): array
    {
        $fees = TransactionFee::where('type', $type)->first();
        $delivery = DeliveryFee::where('state', $state)->value('amount') ?? 0.00;

        if (!$fees) {
            throw new \Exception("No transaction fee found for {$type}");
        }

        return [
            'provider' => $fees->provider,
            'type' => $fees->type,
            'min_amount' => $fees->min,
            'max_amount' => $fees->max,
            'fee' => $fees->fee,
            'delivery_fee' => (float) $delivery,
        ];
    }


}
