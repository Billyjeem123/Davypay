<?php

namespace App\Services;

use App\Helpers\EsimLogger;
use App\Helpers\Utility;
use App\Http\Requests\GlobalRequest;
use App\Http\Resources\EsimResource;
use App\Models\Esim;
use App\Models\PlatformRevenue;
use App\Models\TransactionLog;
use App\Models\Wallet;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EsimService
{

    protected $baseUrl;
    protected $timeout;
    protected $api_key;
    protected $token = null;

    public function __construct()
    {
        $this->baseUrl = config('services.sotel.base_url', 'https://v3.api.termii.com');
        $this->timeout = config('services.sotel.timeout', 30);
        $this->api_key  = config('services.sotel.api_key');
        $this->token = $this->authenticate();
    }


    /**
     * Authenticate with Sotel API and get bearer token
     *
     * @return string
     * @throws \Exception
     */
    public function authenticate(): string
    {
        // If token already fetched, reuse it
        if ($this->token !== null) {
            return $this->token;
        }

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ])
                ->post($this->baseUrl . '/api/esim/authenticate', [
                    'api_key' => $this->api_key,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                EsimLogger::log('Sotel authentication successful');

                $this->token = $data['data']['token']; // cache it
                return $this->token;
            }

            EsimLogger::error('Sotel authentication failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            throw new \Exception('Authentication failed with status: ' . $response->status());

        } catch (\Exception $e) {
            EsimLogger::error('Sotel authentication error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }



    /**
     * Get data plans from Sotel API
     */
    public function getDataPlans(?string $country = null, ?string $type = null): array
    {
        try {
            $queryParams = [];

            if ($country) $queryParams['country'] = $country;
            if ($type) $queryParams['type'] = $type;


            $response = $this->makeAuthenticatedRequest(
                '/api/esim/data/plan/fetch',
                $queryParams,
                'GET'
            );

            EsimLogger::log('Data plans fetched successfully', [
                'plans_count' => count($response['data'] ?? [])
            ]);

            return $response;

        } catch (\Exception $e) {
            EsimLogger::error('Data plans fetch error', [
                'error' => $e->getMessage(),
                'country' => $country,
                'type' => $type
            ]);

            throw $e;
        }
    }

    /**
     * Make authenticated request to Sotel API
     */
    public function makeAuthenticatedRequest(
        string $endpoint,
        array $data = [],
        string $method = 'GET'
    ): array {
        try {
            $token = $this->authenticate(); // fetch only when needed

            $request = Http::timeout($this->timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'X-Token' => $token

                ]);

            Log::info('Sotel API Request', [
                'url' => $this->baseUrl . $endpoint,
                'data' => $data,
                'method' => $method,
            ]);


            $response = match (strtoupper($method)) {
                'GET' => $request->get($this->baseUrl . $endpoint, $data),
                'POST' => $request->post($this->baseUrl . $endpoint, $data),
                'PUT' => $request->put($this->baseUrl . $endpoint, $data),
                'DELETE' => $request->delete($this->baseUrl . $endpoint, $data),
                default => throw new \Exception('Unsupported HTTP method')
            };

            if ($response->successful()) {
                return $response->json();
            }

            $data = $response->json();

            throw new \Exception('Request failed : ' . $data['message']);

        } catch (\Exception $e) {
            Log::error('Sotel API request error', [
                'endpoint' => $endpoint,
                'method' => $method,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Create/Provision a new eSIM
     *
     * @param string $productId
     * @param string $iso3
     * @return array
     * @throws \Exception
     */
    public function createEsim(array $data)
    {
        try {
            $user = Auth::user();
            $wallet = $user->wallet()->first();

            if (!$wallet) {
                return [
                    'success' => false,
                    'message' => 'Wallet not found',
                    'data' => [],
                    'status_code' => 404
                ];
            }

            $balanceBefore = $wallet->amount;
            $totalCost = (float) $data['total_cost'];

            if ($balanceBefore < $totalCost) {
                return [
                    'success' => false,
                    'message' => 'Insufficient funds. Please top up your wallet.',
                    'data' => [],
                    'status_code' => 400
                ];
            }

          #  Wallet::remove_From_wallet($totalCost);
            $this->logTransaction($user, $totalCost,$data, $balanceBefore);
            $requestData = [
                'productId' => $data['productId'],
                'iso3' => $data['iso3']
            ];

            # $response = $this->makeAuthenticatedRequest('/api/esim/create', $requestData, 'POST');
            // Mock API response for now
            $response = $this->mockSimApiResponse();
            $this->storeEsimResponse($response);
            $this->recordEsimProfit($data, $response);

            return [
                'success' => true,
                'message' => 'eSIM created successfully',
                'data' => $response,
                'status_code' => 201
            ];

        } catch (Exception $e) {
            EsimLogger::error('eSIM creation error', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Failed to create eSIM: ' . $e->getMessage(),
                'data' => [],
                'status_code' => 500
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
            'transaction_reference' => Utility::txRef("esim", "system"),
            'service_type' => 'esim_purchase',
            'status' => 'successful',
            'provider' => 'system',
            'channel' => 'Internal',
            'currency' => 'NGN',
            'description' => "Esim purgchase payment",
            'payload' => $data
        ]);
    }

    /**
     * Record platform profit for eSIM
     */
    private function recordEsimProfit(array $data, $response): void
    {
        try {
            $providerCost = (float) $data['provider_cost'];
            $platformMarkup = (float) $data['platform_markup'];
            $totalCost = (float) $data['total_cost'];

            // 💰 Profit = Total charged - Provider cost
            $profit = $totalCost - $providerCost;

            PlatformRevenue::create([
                'user_id'         => Auth::id(),
                'transaction_id'  => uniqid('esim_', true),
                'product_name'    => $data['productId'],
                'type'            => 'esim',
                'status'          => 'successful',
                'amount'          => $totalCost,
                'unit_price'      => $providerCost,
                'commission'      => $platformMarkup,
                'profit'          => $profit,
                'platform'        => 'esim',
                'channel'         => 'web',
                'response_code'   => '000',
                'transaction_date'=> now(),
                'raw_response'    => json_encode($response),
            ]);
        } catch (Exception $e) {
            EsimLogger::error('Error recording eSIM profit', ['error' => $e->getMessage()]);
        }
    }

    private function storeEsimResponse(array $response): void
    {
        if (!isset($response['success']) || !$response['success']) {
            return;
        }

        $data = $response['data']['data'];

        Esim::create([
            'user_id' => auth()->id(),

            // SIM details
            'sim_id'             => $data['sim']['id'] ?? null,
            'iccid'              => $data['sim']['iccid'] ?? null,
            'product_id'         => $data['sim']['productId'] ?? null,
            'imsi'               => $data['sim']['imsi'] ?? null,
            'state'              => $data['sim']['state'] ?? null,
            'last_operation_date'=> $data['sim']['lastOperationDate'] ?? null,
            'activation_code'    => $data['sim']['activationCode'] ?? null,
            'smdp'               => $data['sim']['smdp'] ?? null,
            'purchase_date'      => now(),

            // Data plan
            'plan_product_id'    => $data['dataPlan']['productId'] ?? null,
            'plan_name'          => $data['dataPlan']['name'] ?? null,
            'data_usage_allowance'=> $data['dataPlan']['dataUsageAllowance'] ?? null,
            'time_allowance'     => $data['dataPlan']['timeAllowance'] ?? null,
            'country'            => $data['dataPlan']['country'] ?? null,
            'iso3'               => $data['dataPlan']['iso3'] ?? null,
            'region'             => $data['dataPlan']['region'] ?? null,

            // Response info
            'status'             => $response['data']['status'] ?? null,
            'response_code'      => $response['data']['code'] ?? null,
            'response_message'   => $response['data']['message'] ?? null,
        ]);
    }


    /**
     * Mock API response for SIM creation.
     *
     * @return array
     */
    private function mockSimApiResponse(): array
    {
        return [
            "success" => true,
            "data" => [
                "status" => "Success",
                "code" => 200,
                "data" => [
                    "sim" => [
                        "id" => 394,
                        "iccid" => "8910300000045680927",
                        "productId" => "67f6c113d07af55d502bef7f",
                        "imsi" => "310840118877413",
                        "state" => "INACTIVE",
                        "lastOperationDate" => "2025-09-04 10:04:55",
                        "activationCode" => "TN2025090312194315086BC0",
                        "smdp" => "consumer.e-sim.global",
                        "purchaseDate" => "2025-09-29T11:43:06.294+01:00",
                    ],
                    "dataPlan" => [
                        "productId" => "67f6c113d07af55d502bef7f",
                        "name" => "Africa Bundle-10 GB 30 Days",
                        "dataUsageAllowance" => 10,
                        "timeAllowance" => 30,
                        "country" => "Nigeria",
                        "iso3" => "NGA",
                        "region" => "Africa",
                    ],
                ],
                "message" => "Successful",
                "timestamp" => "2025-09-29 10:43:06",
            ],
            "message" => "eSIM created successfully",
        ];
    }

    public function activateSim(string $iccid): array
    {
        try {

            $endpoint = "/api/esim/activate/{$iccid}/qr/code";
            $response = $this->makeAuthenticatedRequest(
                $endpoint,
                [],
                'GET'
            );
            return $response;

        } catch (\Exception $e) {
            EsimLogger::error('eSIM QR Code fetch error', [
                'error' => $e->getMessage(),
                'iccid' => $iccid,
            ]);

            throw $e;
        }
    }


    public function getEuiccProfile($iccid): array
    {
        try {
            $endpoint = "/api/esim/euicc/{$iccid}";
            $response = $this->makeAuthenticatedRequest(
                $endpoint,
                [],
                'GET'
            );

            return $response;

        } catch (\Exception $e) {
            EsimLogger::error('Failed to fetch euICC profile', [
                'error' => $e->getMessage(),
                'iccid' => $iccid,
            ]);

            throw $e;
        }
    }



    public function getDataUsage($iccid): array
    {
        try {
            $endpoint = "/api/esim/subscriptions/usage/{$iccid}";
            $response = $this->makeAuthenticatedRequest(
                $endpoint,
                [],
                'GET'
            );

            return $response;

        } catch (\Exception $e) {
            EsimLogger::error('Failed to fetch euICC profile', [
                'error' => $e->getMessage(),
                'iccid' => $iccid,
            ]);

            throw $e;
        }
    }


    /**
     * Get list of countries where eSIM services are available
     */
    public function getCountries(int $page = 0, int $size = 15)
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Token'      => $this->token,
        ])->get("{$this->baseUrl}/api/esim/countries/all", [
            'page' => $page,
            'size' => $size,
        ]);


        if ($response->successful()) {
            return $response->json();
        }

        return [
            'success' => false,
            'status'  => $response->status(),
            'error'   => $response->body(),
        ];
    }


    public function UserEsims()
    {
        $userEsims = Esim::where('user_id', Auth::id())
            ->get();

        return EsimResource::collection($userEsims);
    }


}
