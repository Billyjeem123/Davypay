<?php

namespace App\Services;

use App\Helpers\PaymentLogger;
use App\Helpers\Utility;
use App\Models\NombaTransaction;
use App\Models\TransactionLog;
use App\Models\User;
use App\Models\VirtualAccount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;

class NombaService
{
    private $baseUrl;
    private $clientId;
    private $clientSecret;
    public $accountId;

    public function __construct()
    {
        $this->baseUrl = config('services.nomba.base_url');
        $this->clientId = config('services.nomba.client_id');
        $this->clientSecret = config('services.nomba.client_secret');
        $this->accountId =  config('services.nomba.account_id');
    }

    /**
     * Get access token from Nomba API
     *
     * @return array|null
     */
    public function getAccessToken()
    {
        try {
            $cachedToken = Cache::get('nomba_access_token');

            if ($cachedToken && isset($cachedToken['expires_at'])) {
                $expiresAt = Carbon::parse($cachedToken['expires_at']);

                if ($expiresAt->isFuture()) {
                    // Token is still valid
                    return $cachedToken;
                }

                // Token expired - attempt to refresh
                if (isset($cachedToken['refresh_token'])) {
                    $refreshedToken = $this->refreshAccessToken($cachedToken['refresh_token']);
                    if ($refreshedToken && isset($refreshedToken['access_token'])) {
                        return $refreshedToken;
                    }

                    Log::warning('Token expired and refresh failed, will request new one.');
                }
            }

            // No valid or refreshable token - fetch new one
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'accountId' => $this->accountId,
            ])->post($this->baseUrl . '/auth/token/issue', [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);

            if ($response->successful()) {
                $body = $response->json();

                if (!isset($body['data'])) {
                    Log::error('Token response missing "data" key', ['body' => $body]);
                    return null;
                }

                $tokenData = $body['data'];
                $tokenData['expires_at'] = $tokenData['expiresAt'];

                $expiresAt = Carbon::parse($tokenData['expires_at']);
                $cacheExpiry = $expiresAt->diffInMinutes(now()) - 5;

                Cache::put('nomba_access_token', $tokenData, $cacheExpiry);
                return $tokenData;
            } else {
                Log::error('Failed to obtain Nomba access token', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Exception while obtaining Nomba access token', [
                'message' => $e->getMessage(),
                'trace' => null
            ]);
            return null;
        }
    }



    /**
     * Refresh access token using refresh token
     *
     * @param string $refreshToken
     * @return array|null
     */

    public function refreshAccessToken($refreshToken)
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'accountId' => $this->accountId,
            ])->post($this->baseUrl . '/auth/token/refresh', [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
            ]);

            if ($response->successful()) {
                $body = $response->json();

                // Check for the data key
                if (!isset($body['data'])) {
                    Log::error('Token refresh response missing "data" key', ['body' => $body]);
                    return null;
                }

                $tokenData = $body['data'];

                // Normalize `expiresAt` for caching consistency
                $tokenData['expires_at'] = $tokenData['expiresAt'];

                $expiresAt = Carbon::parse($tokenData['expires_at']);
                $cacheExpiry = $expiresAt->diffInMinutes(now()) - 5;

                Cache::put('nomba_access_token', $tokenData, $cacheExpiry);

                return $tokenData;
            } else {
                Log::error('Failed to refresh Nomba access token', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Exception while refreshing Nomba access token', [
                'message' => $e->getMessage(),
                'trace' => null
            ]);
            return null;
        }
    }


    /**
     * Make authenticated request to Nomba API
     *
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @return \Illuminate\Http\Client\Response|null
     */
    public function makeAuthenticatedRequest($method, $endpoint, $data = [])
    {
        $tokenData = $this->getAccessToken();
        if (!is_array($tokenData) || !isset($tokenData['access_token'])) {
            Log::warning('Access token retrieval failed or missing access_token key.', ['tokenData' => $tokenData]);
            return null;
        }


        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $tokenData['access_token'],
                'accountId' => $this->accountId,
            ])->{strtolower($method)}($this->baseUrl . $endpoint, $data);

            return $response;
        } catch (\Exception $e) {
            Log::error('Exception during authenticated request', [
                'method' => $method,
                'endpoint' => $endpoint,
                'message' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Create virtual account
     *
     * @param array $data
     * @return array|null
     */


    public function testNombaCustomerCreation(array $validatedData): array
    {
        $tempUser = $this->prepareUserDataForNomba($validatedData);
        $customerData = $this->formatNombaCustomerData($tempUser);
        $customerData['provider'] = 'nomba';

        return [
            'success' => true,
            'message' => 'Nomba customer data formatted successfully',
            'data' => $customerData
        ];
    }


    public function testNombaCustomerCreation001(array $validatedData): array
    {
        $tempUser = $this->prepareUserDataForNomba($validatedData);
        $customerData = $this->formatNombaCustomerData($tempUser);

        $nombaService = new NombaService();
        $result = $nombaService->createVirtualAccount($customerData);

        return $result;
    }


    private function prepareUserDataForNomba(array $validatedData): object
    {
        return (object) [
            'first_name' => $validatedData['first_name'],
            'last_name' => $validatedData['last_name'],
            'email' => $validatedData['email'],
            'phone' => $validatedData['phone_number'] ?? null,
        ];
    }


    private function formatNombaCustomerData( $user): array
    {
        return [
            'account_ref' => 'Ref-' . Str::upper(Str::random(10)) . '-' . time(),
            'account_name' => $user->first_name  . " ". $user->last_name ,
            'currency' => "NGN",
            'expected_amount' => null,
            'bvn' => "",
            "expiry_date" => "",

        ];
    }



    public function createVirtualAccount(array $data, $user): array
    {
        try {
            $requestData = [
                'accountRef'  => $data['account_ref'],
                'accountName' => $data['account_name'],
                'currency'    => $data['currency'],
            ];

            if (!empty($data['expected_amount'])) {
                $requestData['expected_amount'] = $data['expected_amount'];
            }

            if (!empty($data['bvn'])) {
                $requestData['bvn'] = $data['bvn'];
            }

            if (!empty($data['expiry_date'])) {
                $requestData['expiry_date'] = Carbon::parse($data['expiry_date'])->format('Y-m-d H:i:s');
            }

            $response = $this->makeAuthenticatedRequest('POST', '/accounts/virtual', $requestData);

            if ($response && $response->successful()) {
                $responseData = $response->json();

                if (!empty($responseData['data'])) {
                    $this->saveVirtualAccount($responseData, $user);

                    return ['success' => true, 'data' => $responseData['data']];
                }
            }

            $errorData = $response ? $response->json() : ['message' => 'No response received'];

            Log::error('Failed to create virtual account', [
                'user_id' => $user->id,
                'error'   => $errorData
            ]);
            # 🔴 THROW ERROR TO ROLL BACK TRANSACTION
            throw new \Exception($errorData['message'] ?? $errorData['description'] ?? 'Virtual account creation failed');

        } catch (\Exception $e) {
            Log::error('Exception while creating virtual account', [
                'message' => $e->getMessage(),
                'trace'   => null,
            ]);

            throw $e; // 🔴 THROW AGAIN TO BE HANDLED IN TRANSACTION
        }
    }



    public function createVirtualAccount00(array $data, $user): array
    {
        try {
            $requestData = [
                'accountRef'  => $data['account_ref'],
                'accountName' => $data['account_name'],
                'currency'    => $data['currency'],
            ];

            if (!empty($data['expected_amount'])) {
                $requestData['expected_amount'] = $data['expected_amount'];
            }

            if (!empty($data['bvn'])) {
                $requestData['bvn'] = $data['bvn'];
            }

            if (!empty($data['expiry_date'])) {
                $requestData['expiry_date'] = Carbon::parse($data['expiry_date'])->format('Y-m-d H:i:s');
            }

            $response = $this->makeAuthenticatedRequest('POST', '/accounts/virtual', $requestData);

            if ($response && $response->successful()) {
                $responseData = $response->json();

                if (!empty($responseData['data'])) {
                    $this->saveVirtualAccount($responseData, $user);

                    return ['success' => true, 'data' => $responseData['data']];
                }
            }

            $errorData = $response ? $response->json() : ['message' => 'No response received'];

            Log::error('Failed to create virtual account', [
                'user_id' => $user->id,
                'error'   => $errorData
            ]);

            return [
                'success' => false,
                'error'   => $errorData['description'] ?? 'Unknown error occurred',
                'status'  => $response ? $response->status() : 500,
            ];
        } catch (\Exception $e) {
            Log::error('Exception while creating virtual account', [
                'message' => $e->getMessage(),
                'trace'   => null,
            ]);

            return [
                'success' => false,
                'error'   => config('app.debug') ? $e->getMessage() : 'Internal server error',
                'status'  => 500,
            ];
        }
    }




    /**
     * Save virtual account to database
     */
    public function saveVirtualAccount(array $data, $user): void
    {
        try {
            $virtualData = $data['data'] ?? null;

            if (!$virtualData) {
                Log::error('Missing virtual account data.');
                return;
            }

            VirtualAccount::create([
                'account_number' => $virtualData['bankAccountNumber'] ?? null,
                'bank_name' => $virtualData['bankName'] ?? 'Unknown',
                'account_name' => $virtualData['bankAccountName'] ?? null,
                'provider' => "nomba",
                'user_id' => $user->id,
                'wallet_id' => User::getWalletIdByUserId($user->id),
                'raw_data' => $virtualData,
                'account_ref' => $virtualData['accountRef'],
                'account_holder_id' => $virtualData['accountHolderId'],
                "bvn" => $virtualData['bvn'] ?? null,
            ]);

            Log::info('Virtual account saved successfully for user ID ' . $user->id);

        } catch (\Exception $e) {
            Log::error('Failed to save virtual account: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'data' => $data
            ]);
        }
    }


    /**
     * Create virtual account with direct parameters
     *
     * @param string $accountRef
     * @param string $accountName
     * @param string $currency
     * @param float|null $expectedAmount
     * @param string|null $bvn
     * @param string|null $expiryDate
     * @return array|null
     */
    public function createVirtualAccountDirect($accountRef, $accountName, $currency = 'NGN', $expectedAmount = null, $bvn = null, $expiryDate = null)
    {
        $data = [
            'account_ref' => $accountRef,
            'account_name' => $accountName,
            'currency' => $currency
        ];

        if ($expectedAmount !== null) {
            $data['expected_amount'] = $expectedAmount;
        }

        if ($bvn !== null) {
            $data['bvn'] = $bvn;
        }

        if ($expiryDate !== null) {
            $data['expiry_date'] = $expiryDate;
        }

        $result = $this->createVirtualAccount($data);
        return $result['success'] ? $result['data'] : null;
    }

    /**
     * Get virtual account details
     *
     * @param string $accountRef
     * @return array
     */
    public function getVirtualAccount($accountRef)
    {
        try {
            $response = $this->makeAuthenticatedRequest('GET', "/accounts/virtual/{$accountRef}");

            if ($response && $response->successful()) {
                $responseData = $response->json();

                return [
                    'success' => true,
                    'data' => $responseData['data']
                ];
            } else {
                $errorData = $response ? $response->json() : ['message' => 'No response received'];

                return [
                    'success' => false,
                    'error' => $errorData['description'] ?? 'Unknown error occurred',
                    'status' => $response ? $response->status() : 500
                ];
            }
        } catch (\Exception $e) {
            Log::error('Exception while retrieving virtual account', [
                'account_ref' => $accountRef,
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'An error occurred while retrieving virtual account',
                'status' => 500
            ];
        }
    }

    /**
     * List virtual accounts
     *
     * @param array $params
     * @return array
     */
    public function listVirtualAccounts($params = [])
    {
        try {
            $queryParams = [];

            // Add pagination parameters if provided
            if (isset($params['page'])) {
                $queryParams['page'] = $params['page'];
            }

            if (isset($params['limit'])) {
                $queryParams['limit'] = $params['limit'];
            }

            $endpoint = '/accounts/virtual';
            if (!empty($queryParams)) {
                $endpoint .= '?' . http_build_query($queryParams);
            }

            $response = $this->makeAuthenticatedRequest('GET', $endpoint);

            if ($response && $response->successful()) {
                $responseData = $response->json();

                return [
                    'success' => true,
                    'data' => $responseData['data']
                ];
            } else {
                $errorData = $response ? $response->json() : ['message' => 'No response received'];

                return [
                    'success' => false,
                    'error' => $errorData['description'] ?? 'Unknown error occurred',
                    'status' => $response ? $response->status() : 500
                ];
            }
        } catch (\Exception $e) {
            Log::error('Exception while listing virtual accounts', [
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'An error occurred while retrieving virtual accounts',
                'status' => 500
            ];
        }
    }

    /**
     * Get token information (for debugging)
     *
     * @return array
     */
    public function getTokenInfo()
    {
        $cachedToken = Cache::get('nomba_access_token');

        if ($cachedToken) {
            return [
                'success' => true,
                'token_cached' => true,
                'business_id' => $cachedToken['businessId'] ?? null,
                'expires_at' => $cachedToken['expiresAt'] ?? null,
                'is_expired' => Carbon::parse($cachedToken['expiresAt'])->isPast()
            ];
        } else {
            return [
                'success' => false,
                'token_cached' => false,
                'message' => 'No token in cache'
            ];
        }
    }

    /**
     * Clear cached token (for testing/debugging)
     *
     * @return array
     */
    public function clearToken()
    {
        Cache::forget('nomba_access_token');

        return [
            'success' => true,
            'message' => 'Token cache cleared'
        ];
    }

    /**
     * Test authentication
     *
     * @return array
     */
    public function testAuthentication()
    {
        $tokenData = $this->getAccessToken();

        if ($tokenData) {
            return [
                'success' => true,
                'message' => 'Authentication successful',
                'business_id' => $tokenData['businessId'],
                'expires_at' => $tokenData['expiresAt'],
                'all' => $tokenData
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Authentication failed'
            ];
        }
    }



    /**
     * Initialize payment/charge to fund wallet
     *
     * @param array $data
     * @return array
     */


    public function initializePayment($data): array
    {
        try {
            $reference = Utility::txRef('wallet-funding', 'nomba', true);

            $requestData = $this->buildNombaCheckoutPayload($data, $reference);

            $response = $this->makeAuthenticatedRequest('POST', '/checkout/order', $requestData);

            if ($response && $response->successful()) {
                $responseData = $response->json();

                $user = Auth::user();
                $transaction = TransactionLog::create([
                    'user_id' => $user->id,
                    'wallet_id' => $user->wallet->id,
                    'type' => 'credit',
                    'amount' => $data['amount'],
                    'category' => 'deposit',
                    'transaction_reference' => $reference,
                    'service_type' => 'wallet_funding',
                    'amount_before' => $user->wallet->amount,
                    'amount_after' => 0.00,
                    'status' => 'pending',
                    'provider' => 'nomba',
                    'channel' => 'nomba_card',
                    'currency' => "NGN",
                    'description' => 'Wallet funding via Nomba',
                    'payload' => [ // Ensure this is casted as array in model
                        'initialized_at' => now(),
                        'ip' => request()->ip(),
                        'paystack_response' => $responseData
                    ],
                ]);

                NombaTransaction::create([
                    'transaction_id' => $transaction->id,
                    'reference' => $reference,
                    'amount' => $data['amount'],
                    'status' => 'pending',
                    'user_id' => $user->id,
                    'order_id' => $responseData['data']['orderReference'],
                    'wallet_id' => $user->wallet->id,
                ]);

                PaymentLogger::log('Transaction initialized', ['reference' => $reference]);

                 $service = new ActivityTracker();
                $service->track(
                    'initialize_wallet_deposit',
                    "Initialized wallet deposit of ₦" . number_format($data['amount']) . " via Nomba for {$user->first_name}",
                    [
                        'user_id' => $user->id,
                        'amount' => $data['amount'],
                        'reference' => $reference,
                        'ip' => request()->ip(),
                        'provider' => 'paystack',
                        'status' => 'pending',
                        'effective' => true,
                    ]
                );

                Log::info('Nomba initialized successfully', [
                    'reference' => $reference,
                    'checkout_link' => $responseData['data']['checkoutLink'] ?? null
                ]);

                return [
                    'success' => true,
                    'data' => $responseData['data']
                ];
            } else {
                $errorData = $response ? $response->json() : ['message' => 'No response received'];

                Log::error('Failed to initialize nomba  payment', [
                    'reference' => $reference,
                    'error' => $errorData
                ]);

                return [
                    'success' => false,
                    'error' => $errorData['description'] ?? 'Unknown error occurred',
                    'status' => $response ? $response->status() : 500
                ];
            }
        } catch (\Exception $e) {
            Log::error('Exception while initializing  nomba payment', [
                'message' => $e->getMessage(),
                'trace' => null
            ]);

            return [
                'success' => false,
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
                'status' => 500
            ];
        }
    }



    private function buildNombaCheckoutPayload(array $data, string $order): array
    {
        return [
            'order' => [
                'callbackUrl' => route('nomba.callback'),
                'customerEmail' => Auth::user()->email,
                'amount' => $data['amount'],
                'currency' => "NGN",
                'orderReference' => $order,
                'customerId' => $this->getNombaAccountHolderId(),
                'accountId' => $this->accountId,
            ],
            'tokenizeCard' => false
        ];
    }


    private function getNombaAccountHolderId(): ?string
    {
        $user = Auth::user();

        $nombaAccount = VirtualAccount::where('user_id', $user->id)
            ->where('provider', 'nomba')
            ->first();

        return $nombaAccount?->account_holder_id;
    }


    /**
     * Verify payment status
     *
     * @param string $orderReference
     * @return array
     */
    public function verifyPayment($orderReference)
    {
        try {
            $response = $this->makeAuthenticatedRequest('GET', "/checkout/order/{$orderReference}");

            if ($response && $response->successful()) {
                $responseData = $response->json();

                return [
                    'success' => true,
                    'data' => $responseData['data']
                ];
            } else {
                $errorData = $response ? $response->json() : ['message' => 'No response received'];

                return [
                    'success' => false,
                    'error' => $errorData['description'] ?? 'Unknown error occurred',
                    'status' => $response ? $response->status() : 500
                ];
            }
        } catch (\Exception $e) {
            Log::error('Exception while verifying payment', [
                'order_reference' => $orderReference,
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'An error occurred while verifying payment',
                'status' => 500
            ];
        }
    }

    /**
     * Submit card details for payment
     *
     * @param string $orderReference
     * @param array $cardData
     * @return array
     */
    public function submitCardDetails($orderReference, $cardData)
    {
        try {
            $requestData = [
                'cardNumber' => $cardData['card_number'],
                'expiryMonth' => $cardData['expiry_month'],
                'expiryYear' => $cardData['expiry_year'],
                'cvv' => $cardData['cvv'],
                'pin' => $cardData['pin'] ?? null,
                'saveCard' => $cardData['save_card'] ?? false
            ];

            $response = $this->makeAuthenticatedRequest('POST', "/checkout/order/{$orderReference}/card", $requestData);

            if ($response && $response->successful()) {
                $responseData = $response->json();

                return [
                    'success' => true,
                    'data' => $responseData['data']
                ];
            } else {
                $errorData = $response ? $response->json() : ['message' => 'No response received'];

                return [
                    'success' => false,
                    'error' => $errorData['description'] ?? 'Unknown error occurred',
                    'status' => $response ? $response->status() : 500
                ];
            }
        } catch (\Exception $e) {
            Log::error('Exception while submitting card details', [
                'order_reference' => $orderReference,
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'An error occurred while processing card details',
                'status' => 500
            ];
        }
    }

    /**
     * Verify OTP for payment
     *
     * @param string $orderReference
     * @param string $otp
     * @return array
     */
    public function verifyOTP($orderReference, $otp)
    {
        try {
            $requestData = [
                'otp' => $otp
            ];

            $response = $this->makeAuthenticatedRequest('POST', "/checkout/order/{$orderReference}/otp", $requestData);

            if ($response && $response->successful()) {
                $responseData = $response->json();

                return [
                    'success' => true,
                    'data' => $responseData['data']
                ];
            } else {
                $errorData = $response ? $response->json() : ['message' => 'No response received'];

                return [
                    'success' => false,
                    'error' => $errorData['description'] ?? 'Unknown error occurred',
                    'status' => $response ? $response->status() : 500
                ];
            }
        } catch (\Exception $e) {
            Log::error('Exception while verifying OTP', [
                'order_reference' => $orderReference,
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'An error occurred while verifying OTP',
                'status' => 500
            ];
        }
    }

    /**
     * Cancel payment
     *
     * @param string $orderReference
     * @return array
     */
    public function cancelPayment($orderReference)
    {
        try {
            $response = $this->makeAuthenticatedRequest('POST', "/checkout/order/{$orderReference}/cancel");

            if ($response && $response->successful()) {
                $responseData = $response->json();

                return [
                    'success' => true,
                    'data' => $responseData['data']
                ];
            } else {
                $errorData = $response ? $response->json() : ['message' => 'No response received'];

                return [
                    'success' => false,
                    'error' => $errorData['description'] ?? 'Unknown error occurred',
                    'status' => $response ? $response->status() : 500
                ];
            }
        } catch (\Exception $e) {
            Log::error('Exception while canceling payment', [
                'order_reference' => $orderReference,
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'An error occurred while canceling payment',
                'status' => 500
            ];
        }
    }



    /**
     * Generate unique payment reference
     *
     * @return string
     */
    private function generateReference()
    {
        return 'REF_' . time() . '_' . uniqid();
    }

    public function getAllBanks()
    {
        $response = $this->makeAuthenticatedRequest('GET', "/transfers/banks");

        if ($response && $response->successful()) {
            $responseData = $response->json();
            return [
                'success' => true,
                'data' => $responseData['data']
            ];
        } else {
            $errorData = $response ? $response->json() : ['message' => 'No response received'];

            return [
                'success' => false,
                'error' => $errorData['description'] ?? 'Unknown error occurred',
                'status' => $response ? $response->status() : 500
            ];
        }
    }


    public function resolveAccountNumber(array $data)
    {
        $payload = [
            'accountNumber' => $data['account_number'],
            'bankCode' => $data['bank_code']
        ];

        $response = $this->makeAuthenticatedRequest('POST', '/transfers/bank/lookup', $payload);

        if ($response && $response->successful()) {
            $responseData = $response->json();

            return [
                'success' => true,
                'data' => [
                    'account_number' => $responseData['data']['accountNumber'] ?? null,
                    'account_name' => $responseData['data']['accountName'] ?? null,
                    'raw' => $responseData
                ]
            ];
        }

        $errorData = $response ? $response->json() : ['description' => 'No response received'];

        return [
            'success' => false,
            'error' => $errorData['description'] ?? 'Unknown error occurred',
            'status' => $response ? $response->status() : 500
        ];
    }






}
