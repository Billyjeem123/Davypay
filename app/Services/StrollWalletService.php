<?php

namespace App\Services;

use App\Helpers\Utility;
use App\Helpers\VirtualLogger;
use App\Models\Settings;
use App\Models\TransactionLog;
use App\Models\User;
use App\Models\UserVirtualCard;
use App\Models\VirtualCard;
use App\Models\Wallet;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StrollWalletService
{


    protected $baseUrl;
    protected $publicKey;

    public function __construct()
    {
        $this->baseUrl = config('services.strowallet.base_url', 'https://strowallet.com/api');
        $this->publicKey = config('services.strowallet.public_key');
    }




    public function createAccount(): array
    {
        $user = Auth::user();

        try {
            $userData = $this->getVirtualUserPayload($user);

            $response = $this->makeApiCall('/bitvcard/create-user/', $userData);

            if (!($response['success'] && ($response['data']['success'] ?? false))) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => $response['data']['message'] ?? 'Failed to create card user',
                    'status_code' => $response['status_code'] ?? 400
                ];
            }
            $apiResponseData  = $this->saveUserVirtualData($response['data']['response']);
            DB::commit();
            return [
                'success' => true,
                'message' => 'Strowallet created successfully',
                'data' => [
                    'customer' => $apiResponseData,
                ]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            VirtualLogger::log('Error creating Strowallet card user or card',  ['error' => Utility::getExceptionDetails($e)]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function createCard($data): array
    {

        $user = Auth::user();
        $wallet = $user->wallet()->first();
        $balanceBefore = $wallet->amount;
        if ($balanceBefore < $data['cost']) {
            return [
                'success' => false,
                'message' => 'Insufficient funds',
                'data' => [],
                'status_code' => 400
            ];
        }

        DB::beginTransaction();

        try {
            $cardResponse = $this->createVirtualCard([
                'name_on_card' => $user->first_name . ' ' . $user->last_name,
                'card_type'    => 'visa'
            ]);

            if (empty($cardResponse['success']) || $cardResponse['success'] === false) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => $cardResponse['message'] ?? 'Failed to create card',
                    'status_code' => 400
                ];
            }

         #   Wallet::remove_From_wallet($data['cost']);
            $this->logTransaction($user, $data['cost'], $data, $balanceBefore);
            $this->createVirtualCardFromApiResponse($cardResponse, $user);

            DB::commit();
            return [
                'success' => true,
                'message' => 'Strowallet card created successfully',
                'data' => [
                    'card' => $cardResponse['data'] ?? []
                ]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            VirtualLogger::log('Error creating Strowallet card user or card',  ['error' => Utility::getExceptionDetails($e)]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }




    public  function updateCardCustomer($data)
    {
        $user = Auth::user();
        $document = $this->getUserDocumentDetails($user);

        try {
            $payload = [
                'public_key'  => $this->publicKey,
                'customerId'  => $user->kyc->customerId,
                'firstName'   => $user->first_name,
                'lastName'    => $user->last_name,
                'idImage'     => $document['idImage'] ?? '',
                'userPhoto'   => $document['selfie_image'] ?? '',
                'phoneNumber' => $user->phone ?? '',
                'country'     => $user->country ?? '',
                'city'        => $user->city ?? '',
                'state'       => $user->state ?? '',
                'zipCode'     => $user->zip_code ?? '',
                'line1'       => $document['address'] ?? '',
                'houseNumber' => $document['address'] ?? '12',
            ];

            return $this->makeApiCall('/bitvcard/updateCardCustomer/', $payload, 'PUT');

        } catch (\Exception $e) {
            VirtualLogger::log('Error updating card customer', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }


    private function logTransaction($user, float $amount, $data,$amountBefore): void
    {
        $wallet = $user->wallet()->first();
        $amountAfter = $wallet->fresh()->amount;

        TransactionLog::create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'type' => 'debit',
            'amount' => $amount,
            'amount_before' => $amountBefore,
            'amount_after' => $amountAfter,
            'transaction_reference' => Utility::txRef("virtual", "system"),
            'service_type' => 'virtual_card',
            'status' => 'successful',
            'provider' => 'system',
            'channel' => 'Internal',
            'currency' => 'NGN',
            'description' => "Card Creation and fund payment",
            'payload' => $data
        ]);
    }


    public function createVirtualCard(array $cardData = null): array
    {
        $user = auth()->user();
        $kycCard = $user->virtual_cards;

        if (!$kycCard) {
            return [
                'success' => false,
                'message' => 'No Strowallet card user found',
                'data' => [],
            ];
        }

        $payload = [
            'name_on_card'   => $cardData['name_on_card'],
            'card_type'      => $cardData['card_type'] ?? 'visa',
            'public_key'     => $this->publicKey,
            'amount'         => 3,
            'customerEmail'  => $user->email,
            'mode'           => 'sandbox'
        ];
        $endpoint = '/bitvcard/create-card/';
        $response = $this->makeApiCall($endpoint, $payload, 'POST');
        if ($response['success']) {
            return [
                'success' => true,
                'message' => $response['message'] ?? 'Virtual card created.',
                'data' => $response,
            ];
        }

        return [
            'success' => false,
            'message' => $response['message'] ?? 'An error occurred during card creation',
            'data' => $response
        ];
    }


    public function changeEnvironment(): string
    {
        $env = app()->environment(); // gets 'local', 'production', etc.

        if ($env === 'local') {
            return 'sandbox';
        }

        return 'live';
    }


    private function createVirtualCardFromApiResponse(array $data, User $user): void
    {
        $cardResponseData = $data['data']['data']['response'] ?? [];

        if (!empty($cardResponseData)) {
            UserVirtualCard::create([
                'user_id'           => $user->id,
                'card_id'           => $cardResponseData['card_id'] ?? null,
                'card_status'       => $cardResponseData['card_status'] ?? null,
                'name'              => $cardResponseData['name_on_card'] ?? null,
                'brand'             => $cardResponseData['card_brand'] ?? null,
                'type'              => $cardResponseData['card_type'] ?? null,
                'reference'         => $cardResponseData['reference'] ?? null,
                'customer_id'       => $cardResponseData['customer_id'] ?? null,
                'provider_user_id'  => $cardResponseData['card_user_id'] ?? null,
                'api_response'      => $cardResponseData,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }
    }



    protected function saveUserVirtualData(array $userData): VirtualCard
    {
        return VirtualCard::create([
            'first_name' =>  $userData['firstName'],
            'last_name' =>  $userData['lastName'],
            'email' =>  $userData['customerEmail'],
            'phone' =>  $userData['phoneNumber'],
            'country' =>  $userData['country'],
            'state' =>  $userData['state'],
            'city' =>  $userData['city'],
            'provider' => 'strowallet',
            'type' => 'strowallet',
            'address' => $userData['line1'],
            'zip_code' => $userData['zipCode'],
            'id_type' =>  $userData['idType'],
            'id_number' => $userData['idNumber'],
            'user_id' => Auth::id(),
            'provider_user_id' => $userData['customerId'],
            'customerId' => $userData['customerId'],
            'card_status' => "null",
            'api_response' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }




    private function getVirtualUserPayload($user): array
    {
        $kyc = $user->kyc;
        $document = $this->getUserDocumentDetails($user);
        return [
            'public_key'    => $this->publicKey,
            'firstName'     => $user->first_name,
            'lastName'      => $user->last_name,
            'idNumber'      => $document['idNumber'],
            'idType'        => $document['idType'],
            'customerEmail' =>  $user->email,
            'phoneNumber'   => $this->validatePhoneNumber($user->phone),
            'dateOfBirth'   => $kyc->dob ,
            'idImage'       => $document['idImage'],
            'userPhoto'     => $document['selfie_image'],
            'line1'         => $document['address'],
            'houseNumber'   => $kyc->house_number ?? '12',
            'state'         => $kyc->state ?? 'Lagos',
            'zipCode'       => $kyc->postal_code ?? '100001',
            'city'          => $kyc->city ?? 'Ikeja',
            'country'       => "NIGERIA"
        ];
    }

    public function generateRandomEmail(): string
    {
        $randomUsername = Str::lower(Str::random(10));
        return $randomUsername . '@gmail.com';
    }

    private function validatePhoneNumber(string $phone): string
    {
        #  Remove spaces or special characters just in case
        $phone = preg_replace('/\D/', '', $phone);

        #  If it starts with 0 and is 11 digits (Nigerian format e.g. 08117283227)
        if (preg_match('/^0\d{10}$/', $phone)) {
            return '234' . substr($phone, 1);
        }

        #  If it already starts with 234 and has the right length, return as is
        if (preg_match('/^234\d{10}$/', $phone)) {
            return $phone;
        }

        #  Otherwise return original (or throw exception if you want strict)
        return $phone;
    }



    private function getUserDocumentDetails($user): array
    {
        $type = $user->kyc->verification_type ?? null;
        $documentId = $user->kyc->verification_value ?? null;

        $documentType = match ($type) {
            'NG-NIN-SLIP' => 'NIN',
            'PASSPORT_ID' => 'Passport',
            default => 'Unknown',
        };

        return [
            'idNumber' => $documentId,
            'idType'   => $documentType,
            'idImage'  => $user->kyc->id_image_url,
            'selfie_image' => $user->kyc->selfie ?? $user->kyc->selfie_image,
            'address'  => $user->kyc->address ?? "Lagos Nigeria"

        ];
    }


    public function getVirtualCardCustomer(): array
    {
        $user = auth()->user();
        $kycCard = $user->virtual_card;
        if (!$kycCard) {
            return [
                'success' => false,
                'message' => 'No Strowallet card found for user',
                'data' => [],
            ];
        } ;
        $queryParams = [
            'customerId'    => $kycCard->customerId,
            'customerEmail' => $kycCard->email,
            'public_key'    => $this->publicKey,
        ];

        $endpoint = '/bitvcard/getcardholder/';
        $response = $this->makeApiCall($endpoint . '?' . http_build_query($queryParams), [], 'GET');

        if ($response['success'] && isset($response['data']['data'])) {
            return [
                'success' => true,
                'message' => 'Customer details fetched successfully.',
                'data' => [
                    'customer_data' =>  $response['data']['data'],
                    'card_details'  => $user->virtual_cards,
                ],
            ];
        }

        return [
            'success' => false,
            'message' => $response['message'] ?? 'Failed to fetch customer details',
            'data' => [],
        ];
    }





    public function getCardDetails($cardId): array
    {
        $queryParams = [
            'public_key' => $this->publicKey,
            'card_id'    => $cardId,
             'mode'       => 'sandbox' // Optional for live mode
        ];

        $endpoint = '/bitvcard/fetch-card-detail/';

        $response = $this->makeApiCall($endpoint, $queryParams);

        if (
            $response['success'] &&
            isset($response['data']['response']['card_detail'])
        ) {
            return [
                'success' => true,
                'message' => 'Card details retrieved successfully.',
                'data'    => $response['data']['response']['card_detail'],
            ];
        }
        return [
            'success' => false,
            'message' => $response['message'] ?? 'Failed to fetch card details.',
            'data'    => [],
        ];
    }




    public function getCardTransactions($cardId): array
    {
        $queryParams = [
            'public_key' => $this->publicKey,
            'card_id'    => $cardId,
             'mode'       => 'sandbox' // Optional: remove in production
        ];

        $endpoint = '/bitvcard/card-transactions/';
        $response = $this->makeApiCall($endpoint, $queryParams, 'POST'); // Assuming POST is required

        // Check response structure
        if (
            $response['success'] &&
            isset($response['data']['response']['card_transactions'])
        ) {
            return [
                'success' => true,
                'message' => 'Card transactions retrieved successfully.',
                'data'    => $response['data']['response']['card_transactions'],
            ];
        }

        return [
            'success' => false,
            'message' => $response['message'] ?? 'Failed to fetch card transactions.',
            'data'    => [],
        ];
    }

    public function processCardFunding(array $validated): array
    {
        DB::beginTransaction();
        try {
            $conversionRate = Settings::get('dollar_conversion_rate', 1500);
            $amountInUSD = $validated['amount'];
            $amountInNGN = $amountInUSD * $conversionRate;

            // Check balance
            $walletBalance = Wallet::check_balance();
            if ($walletBalance < $amountInNGN) {
                return [
                    'success' => false,
                    'message' => 'Insufficient wallet balance for this transaction',
                    'data'    => [],
                ];
            }

            $userData = $this->getFundingPayload($validated);
            $response = $this->makeApiCall('/bitvcard/fund-card/', $userData);

            # Handle failed API response
            if (!$response['success'] || $response['success'] != true) {
                return [
                    'success' => false,
                    'message' => $response['message'] ?? 'Card funding failed',
                    'data'    => [],
                ];
            }

            $apiData = $response['data']['apiresponse']['data'] ?? [];
            $apiMessage = $response['data']['apiresponse']['message'] ?? 'Card funding processed';
            $apiStatus = $apiData['status'] ?? 'pending';

            #$newBalance = Wallet::remove_From_wallet($amountInNGN);

            $user = Auth::user();
            $wallet = Wallet::where('user_id', $user->id)->first();

            TransactionLog::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'type' => 'debit',
                'category' => 'virtual_card_funding',
                'amount' => $amountInNGN,
                'transaction_reference' => $apiData['reference'] ?? 0,
                'service_type' => 'virtual_card',
                'amount_before' => $walletBalance,
                'amount_after' => $newBalance ?? 0,
                'status' => $apiStatus, // Sync Strowallet status (e.g. pending, success)
                'provider' => 'strowallet',
                'channel' => 'api',
                'currency' => 'NGN',
                'description' => "Funded virtual card with \${$amountInUSD} (₦" . number_format($amountInNGN, 2) . ")",
                'provider_response' => json_encode($response),
                'payload' => $validated,
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => $apiMessage,
                'data' => [
                    'status' => $apiStatus,
                    'card_id' => $apiData['cardId'] ?? null,
                    'narrative' => $apiData['narrative'] ?? null,
                ],
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error funding card: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'An unexpected error occurred: ' . $e->getMessage(),
                'status_code' => 500,
            ];
        }
    }



    private function getFundingPayload($data): array
    {
        return [
            'card_id'     => $data['card_id'],
            'amount'      => $data['amount'],
            'public_key'  => $this->publicKey,
            'mode'        => "sandbox",
        ];
    }




    protected function makeApiCall(string $endpoint, array $data = [], string $method = 'POST'): array
    {
        try {
            $http = Http::withHeaders([
                'Content-Type' => 'application/json',
            ]);

            $response = match (strtoupper($method)) {
                'POST' => $http->post($this->baseUrl . $endpoint, $data),
                'PATCH' => $http->patch($this->baseUrl . $endpoint, $data),
                'PUT' => $http->put($this->baseUrl . $endpoint, $data),
                'GET' => $http->get($this->baseUrl . $endpoint),
                default => throw new \Exception("Unsupported HTTP method: {$method}")
            };

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'status_code' => $response->status()
                ];
            }
            return [
                'success' => false,
                'message' => $response->json()['message'] ?? 'API request failed',
                'data' => $response->json()
            ];
        } catch (\Exception $e) {
            VirtualLogger::log('Strowallet API call failed: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'API connection failed: ' . $e->getMessage(),
                'status_code' => 500
            ];
        }
    }




    public function processCardWithdrawal(array $validated): array
    {
        DB::beginTransaction();

        try {
            $user = Auth::user();
            $wallet = Wallet::where('user_id', $user->id)->first();

            $amountInUSD = $validated['amount'];
            $conversionRate = Settings::get('dollar_conversion_rate', 1500);
            $amountInNGN = $amountInUSD * $conversionRate;

            $payload = [
                'card_id'    => $validated['card_id'],
                'amount'     => $validated['amount'],
                'public_key' => $this->publicKey,
            ];

            $queryString = http_build_query($payload);
            $endpoint = '/bitvcard/card_withdraw/?' . $queryString;

            $response = $this->makeApiCall($endpoint, [], 'POST');

            if (empty($response['success']) || $response['success'] === false) {
                return [
                    'success' => false,
                    'message' => $response['message'] ?? 'Card withdrawal failed',
                    'data' => [
                        'api_response' => $response['data'] ?? [],
                    ],
                    'status_code' => $response['status_code'] ?? 400,
                ];
            }

            TransactionLog::create([
                'user_id'               => $user->id,
                'wallet_id'             => $wallet->id,
                'type'                  => 'credit', // will credit when confirmed
                'category'              => 'virtual_card_withdrawal',
                'amount'                => $amountInNGN,
                'transaction_reference' => Utility::txRef("virtual", "withdrawal"),
                'service_type'          => 'virtual_card',
                'amount_before'         => $wallet->amount,
                'amount_after'          => $wallet->amount, // unchanged until webhook
                'status'                => 'pending', // waiting for webhook
                'provider'              => 'strowallet',
                'channel'               => 'internal',
                'currency'              => 'NGN',
                'description'           => "Withdrawal of \${$amountInUSD} (₦" . number_format($amountInNGN, 2) . ") from virtual card requested.",
                'provider_response'     => json_encode($response),
                'payload'               => $validated,
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => $response['data']['message'] ?? 'Withdrawal initiated successfully. Awaiting confirmation.',
                'data'    => [
                    'withdrawal_status' => $response['data']['data']['status'] ?? 'pending',
                    'transaction_ref'   => $response['data']['data']['reference'] ?? null,
                    'amount_usd'        => $amountInUSD,
                    'amount_ngn'        => $amountInNGN,
                ],
                'status_code' => 200,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing card withdrawal: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [Utility::getExceptionDetails($e)],
                'status_code' => 500
            ];
        }
    }



    public function getVirtualSettings(): array
    {
        return [
            'virtual_card_topup_fee'     => number_format((float) Settings::get('virtual_card_topup_fee', 0), 2, '.', ''),
            'virtual_card_creation_fee'  => number_format((float) Settings::get('virtual_card_creation_fee', 0), 2, '.', ''),
            'virtual_card_account_fee'   => number_format((float) Settings::get('virtual_card_account_fee', 0), 2, '.', ''),
            'dollar_conversion_rate'   => Settings::get('dollar_conversion_rate', 1500),
        ];
    }


    public function processCardUnFreezing(array $validated): array
    {
        // Default action to 'freeze' if not provided
        $action = $validated['action'] ?? 'freeze';
        DB::beginTransaction();

        $queryParams = http_build_query([
            'action'     => $action,
            'card_id'    => $validated['card_id'],
            'public_key' => $this->publicKey,
        ]);
        $endpoint = '/bitvcard/action/status/?' . $queryParams;
        $response = $this->makeApiCall($endpoint, [], 'POST');

        if (!empty($response['success']) && $response['success'] === true) {
            DB::commit();
            return [
                'success' => true,
                'message' => $response,
            ];
        }

        DB::rollBack();

        return [
            'success' => false,
            'message' => $response['message'] ?? 'Failed to process card action',
            'status_code' => $response['status_code'] ?? 400
        ];
    }


    public function syncCardWithdrawalStatus(string $reference): array
    {
        try {
            $payload = [
                'public_key' => $this->publicKey,
                'reference'  => $reference,
            ];

            $query = http_build_query($payload);
            $endpoint = '/bitvcard/getcard_withdrawstatus/?' . $query;

            $response = $this->makeApiCall($endpoint, [], 'GET');

            if (empty($response['success']) || $response['success'] === false) {
                return [
                    'success' => false,
                    'message' => $response['message'] ?? 'Unable to fetch withdrawal status',
                    'data'    => $response,
                    'status_code' => 400,
                ];
            }

            $withdrawStatus = strtolower($response['data']['status'] ?? 'pending');

            $transaction = TransactionLog::where('transaction_reference', $reference)->first();
            if (!$transaction) {
                return [
                    'success' => false,
                    'message' => 'Transaction not found',
                    'data' => [],
                    'status_code' => 404,
                ];
            }

            $wallet = Wallet::find($transaction->wallet_id);

            $newStatus = match ($withdrawStatus) {
                'approved', 'success', 'completed' => 'completed',
                'failed', 'declined' => 'failed',
                default => 'pending',
            };

            if ($newStatus === 'completed' && $wallet) {
                $wallet->increment('amount', $transaction->amount);
            }

            $transaction->update([
                'status' => $newStatus,
                'provider_response' => json_encode($response),
                'amount_after' => $wallet ? $wallet->amount : $transaction->amount_after,
            ]);

            return [
                'success' => true,
                'message' => "Withdrawal status synced successfully.",
                'data' => [
                    'status' => $newStatus,
                    'reference' => $reference,
                ],
                'status_code' => 200,
            ];

        } catch (\Exception $e) {
            Log::error('Error syncing withdrawal status: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [],
                'status_code' => 500,
            ];
        }
    }



}
