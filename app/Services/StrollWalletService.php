<?php

namespace App\Services;

use App\Helpers\Utility;
use App\Helpers\VirtualLogger;
use App\Models\Settings;
use App\Models\TransactionLog;
use App\Models\User;
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



    public function createCardUser(): array
    {
        DB::beginTransaction();
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


            $apiResponseData = $response['data']['response'] ?? [];
            $this->storeVirtualCard($userData, $apiResponseData);

            $cardResponse = $this->createVirtualCard([
                'name_on_card' => $apiResponseData['name_on_card'] ?? $user->first_name . ' ' . $user->last_name,
                'card_type'    => $apiResponseData['card_type'] ?? 'visa'
            ]);
            $this->updateVirtualCardWithApiResponse($cardResponse, $user);

            if (!$cardResponse['success']) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => $cardResponse['message'] ?? 'Failed to create card',
                    'status_code' => 400
                ];

            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Strowallet card user and card created successfully',
                'data' => [
                    'customer' => $apiResponseData,
                    'card' => $cardResponse['data']['response'] ?? []
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


    public function createVirtualCard(array $cardData = null): array
    {
        $user = auth()->user();
        $kycCard = $user->virtual_cards->first();

        if (!$kycCard) {
            return [
                'success' => false,
                'message' => 'No Strowallet card user found',
                'data' => [],
            ];
        }

        $payload = [
            'name_on_card'   => $cardData['name_on_card'] ?? $user->first_name . ' ' . $user->last_name,
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
            'data' => $response['errors'] ?? [],
        ];
    }



    private function updateVirtualCardWithApiResponse(array $data, User $user): void
    {
        $card = VirtualCard::where('user_id', $user->id)
            ->where('provider', 'strowallet')
            ->latest()
            ->first();

        // Navigate safely to response
        $cardResponseData = $data['data']['data']['response'] ?? [];

        if ($card && !empty($cardResponseData)) {
            $card->update([
                'card_id'           => $cardResponseData['card_id'],
                'card_status'       => $cardResponseData['card_status'] ?? null,
                'name'              => $cardResponseData['name_on_card'] ?? null,
                'brand'             => $cardResponseData['card_brand'] ?? null,
                'type'              => $cardResponseData['card_type'] ?? null,
                'reference'         => $cardResponseData['reference'] ?? null,
                'customer_id'       => $cardResponseData['customer_id'] ?? null,
                'provider_user_id'  => $cardResponseData['card_user_id'] ?? null,
                'api_response'      => json_encode($cardResponseData),
                'updated_at'        => now(),
            ]);
        }
    }




    protected function storeVirtualCard(array $userData, array $apiResponse): VirtualCard
    {
        return VirtualCard::create([
            'first_name' => $apiResponse['firstName'] ?? $userData['firstName'],
            'last_name' => $apiResponse['lastName'] ?? $userData['lastName'],
            'email' => $apiResponse['customerEmail'] ?? $userData['customerEmail'],
            'phone' => $apiResponse['phoneNumber'] ?? $userData['phoneNumber'],
            'country' => $apiResponse['country'] ?? $userData['country'],
            'state' => $apiResponse['state'] ?? $userData['state'],
            'city' => $apiResponse['city'] ?? $userData['city'],
            'provider' => 'strowallet',
            'type' => 'strowallet',
            'address' => $apiResponse['line1'] ?? $userData['line1'],
            'zip_code' => $apiResponse['zipCode'] ?? $userData['zipCode'],
            'id_type' => $apiResponse['idType'] ?? $userData['idType'],
            'id_number' => $apiResponse['idNumber'] ?? $userData['idNumber'],
            'user_id' => Auth::id(),
            'provider_user_id' => $apiResponse['customerId'] ?? null,
            'card_status' => null,
            'api_response' => $apiResponse,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }




    /**
     * Get sample Strowallet API response
     *
     * @return array
     */
    public function getSampleStrowalletResponse(): array
    {
        return [
            "success" => true,
            "data" => [
                "success" => true,
                "message" => "successfully registered user",
                "response" => [
                    "bvn" => "22035074465",
                    "customerEmail" => "billyhadiattaofeeq@gmail.com",
                    "firstName" => "ROBERT",
                    "lastName" => "OGUNDIRAN",
                    "phoneNumber" => "2348117283226",
                    "city" => "Ikeja",
                    "state" => "Lagos",
                    "country" => "NIGERIA",
                    "line1" => "Ikeja,Nigeria",
                    "zipCode" => "100001",
                    "houseNumber" => "12",
                    "idNumber" => "22547614959",
                    "idType" => "PASSPORT",
                    "idImage" => "https://images.dojah.io/image_68ae310659d05e0047a59d83id_1756246347.jpg",
                    "userPhoto" => "https://example.com/selfie.jpg",
                    "customerId" => "643d44c1-e128-4663-954b-5c0b19ddf6df",
                    "dateOfBirth" => "1996-07-28"
                ]
            ],
            "status_code" => 200
        ];
    }

    private function getVirtualUserPayload($user): array
    {
        $kyc = $user->kyc;
        $document = $this->getUserDocumentDetails($user);

        return [
            'public_key'    => $this->publicKey,
            'firstName'     => $user->first_name,
            'lastName'      => $user->last_name,
//            'idNumber'      => $user->kyc->bvn ?? $user->kyc->nin,
              "idNumber"  =>  random_int(1000000000, 9999999999),
              'idType'        => "passport",
            'customerEmail' =>  $this->generateRandomEmail(),
            'phoneNumber'   => $this->validatePhoneNumber($user->phone),
            'dateOfBirth'   => $kyc->dob ,
//            'idImage'       => $document['idImage'],
              'idImage'       =>   "https://example.jpeg",
            'userPhoto'     => $document['selfie_image'],
            'line1'         => $kyc->address ?? 'Address Line',
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
        $documentType = $type === "NG-NIN-SLIP"
            ? "NIN"
            : "Passport";

        return [
            'idNumber' => $documentId,
            'idType'   => $documentType,
            'idImage'  => $user->kyc->id_image_url,
            'selfie_image' => $user->kyc->selfie_image_url ?? 'https://example.com/selfie.jpg',

        ];
    }




    public function getVirtualCardCustomer(): array
    {
        $user = auth()->user();
        $kycCard = $user->virtual_cards->first();

        if (!$kycCard) {
            return [
                'success' => false,
                'message' => 'No Strowallet card found for user',
                'data' => [],
            ];
        }
        $queryParams = [
            'customerId'    => $kycCard->provider_user_id,
            'customerEmail' => $user->email,
            'public_key'    => $this->publicKey,
        ];
        $endpoint = '/bitvcard/getcardholder/';
        $response = $this->makeApiCall($endpoint . '?' . http_build_query($queryParams), [], 'GET');

        if ($response['success'] && isset($response['data']['data'])) {
            return [
                'success' => true,
                'message' => 'Customer details fetched successfully.',
                'data' => $response['data']['data'],
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
            $conversionRate = Settings::get('dollar_conversion_rate', 0);
            if ($conversionRate < 1) {
                throw new \Exception('Dollar conversion rate is not properly configured.');
            }

            $amountInUSD = $validated['amount'];
            $amountInNGN = $amountInUSD * $conversionRate;

            # Check balance
            $walletBalance = Wallet::check_balance();
            if ($walletBalance < $amountInNGN) {
                throw new \Exception('Insufficient wallet balance for this transaction.');
            }

            # Make API call BEFORE deducting from wallet
            $userData = $this->getFundingPayload($validated);
            $response = $this->makeApiCall('/bitvcard/fund-card/', $userData);
            if (!$response['success']) {
                throw new \Exception($response['message'] ?? 'Card funding failed');
            }

            # Only deduct from wallet after successful API call
            $newBalance = Wallet::remove_From_wallet($amountInNGN);

            # Log the transaction
            $user = Auth::user();
            $wallet = Wallet::where('user_id', $user->id)->first();
            TransactionLog::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'type' => 'debit',
                'category' => 'virtual_card_funding',
                'amount' => $amountInNGN,
                'transaction_reference' => Utility::txRef("virtual", "virtual_card"),
                'service_type' => 'virtual_card',
                'amount_before' => $walletBalance,
                'amount_after' => $newBalance,
                'status' => 'pending',
                'provider' => 'system',
                'channel' => 'internal',
                'currency' => 'NGN',
                'description' => "Funded virtual card with \${$amountInUSD} (₦" . number_format($amountInNGN, 2) . ")",
                'provider_response' => json_encode($response),
                'payload' => json_encode($validated),
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Transaction successful',
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error funding card: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }


    private function getFundingPayload($data): array
    {
        return [
            'card_id'     => $data['card_id'],
            'amount'      => $data['amount'],  // required!
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
            $amountInUSD = $validated['amount'];
            $conversionRate = Settings::get('dollar_conversion_rate', 0);
            if ($conversionRate < 1) {
                throw new \Exception('Dollar conversion rate is not properly configured.');
            }

            $amountInNGN = $amountInUSD * $conversionRate;

            $payload = [
                'card_id'     => $validated['card_id'],
                'amount'      => $amountInUSD,
                'public_key'  => $this->publicKey,
            ];

            $response = $this->makeApiCall('/bitvcard/card_withdraw/', $payload, 'POST');

            if (!$response['success']) {
                throw new \Exception($response['message'] ?? 'Card withdrawal failed');
            }

            // Log the withdrawal as pending – wait for webhook to confirm
            $user = Auth::user();
            $wallet = Wallet::where('user_id', $user->id)->first();

            TransactionLog::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'type' => 'credit', // Wallet will be credited when webhook confirms
                'category' => 'virtual_card_withdrawal',
                'amount' => $amountInNGN,
                'transaction_reference' => Utility::txRef("virtual", "virtual_card"),
                'service_type' => 'virtual_card',
                'amount_before' => $wallet->balance,
                'amount_after' => $wallet->balance, // Still unchanged
                'status' => 'pending', // Will change to successful when webhook confirms
                'provider' => 'system',
                'channel' => 'internal',
                'currency' => 'NGN',
                'description' => "Requested withdrawal of \${$amountInUSD} (₦" . number_format($amountInNGN, 2) . ") from virtual card",
                'provider_response' => json_encode($response),
                'payload' => json_encode($validated),
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Withdrawal request submitted successfully. Awaiting confirmation.',
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing card withdrawal: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }


# Funding a Virtual Card This means the user is moving money from their wallet (in your app) into their virtual card balance.
# Withdrawal from a Virtual Card

}
