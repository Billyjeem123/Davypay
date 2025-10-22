<?php

namespace App\Services;

use App\Helpers\FlightLogger;
use App\Models\TransactionLog;
use App\Models\Wallet;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class WakanowService
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    protected ?string $token = null;

    public function __construct()
    {
        $this->baseUrl  = config('services.wakanow.base_url');
        $this->username = config('services.wakanow.username');
        $this->password = config('services.wakanow.password');
    }

    public function authenticate(): array
    {
        try {
            $response = Http::asForm()
                ->timeout(30)
                ->post($this->baseUrl . '/token', [
                    'grant_type' => 'password',
                    'username'   => $this->username,
                    'password'   => $this->password,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                $accessToken = $data['access_token'] ?? null;
                $expiresIn   = $data['expires_in'] ?? 3500;

                if ($accessToken) {
                    Cache::put('wakanow_access_token', $accessToken, $expiresIn - 60);
                }
                FlightLogger::log("Wakanow authentication successful", $data);


                return [
                    'success' => true,
                    'message' => 'Authentication successful',
                    'data'    => $data,
                ];
            }

            $errorData = $response->json();
            FlightLogger::error("Wakanow authentication failed", [
                'status_code' => $response->status(),
                'response' => $errorData
            ]);

            return [
                'success' => false,
                'message' => $errorData['error_description'] ?? 'Authentication failed',
                'data'    => $errorData,
            ];

        } catch (Exception $e) {
            FlightLogger::error("Wakanow Auth error: {$e->getMessage()}");
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => [],
            ];
        }
    }

    /**
     * Always use cached token if valid
     */
    private function withAuth()
    {
        $token = Cache::get('wakanow_access_token');

        if (!$token) {
            $auth = $this->authenticate();
            if (!$auth['success']) {
                throw new Exception("Failed to authenticate: " . $auth['message']);
            }
            $token = $auth['data']['access_token'] ?? null;
        }

        return Http::withToken($token)
            ->acceptJson()
            ->timeout(60)
            ->withHeaders([
                'Accept-Encoding' => 'gzip, deflate',
                'Content-Type' => 'application/json'
            ]);
    }


    /**
     * Enhanced service method with better error handling
     */
    public function searchFlights(array $payload): array
    {
        $endpoint = '/api/flight/search';

        FlightLogger::log("Wakanow Flight Search Request", [
            'endpoint' => $endpoint,
            'payload'  => $this->sanitizeLogData($payload),
            'timestamp' => now()->toISOString()
        ]);

        try {
            $response = $this->request('post', $endpoint, $payload);
            FlightLogger::log("Wakanow Raw Flight Search Response", [
                'raw_response' => $response
            ]);

            FlightLogger::log("Wakanow Flight Search Response Details", [
                'has_data' => isset($response['data']) && !empty($response['data']),
                'data_count' => isset($response['data']) ? count($response['data']) : 0,
                'response_keys' => array_keys($response),
                'status' => $response['status'] ?? 'unknown'
            ]);

            return $response;

        } catch (\Exception $e) {
            FlightLogger::error("Wakanow API Error", [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
                'payload' => $this->sanitizeLogData($payload)
            ]);

            throw $e;
        }
    }


    /**
     * Flight Select (get price details using SelectData from search)
     */
    public function selectFlight(array $params): array
    {
        if (empty($params['selectData'])) {
            return $this->errorResponse("Missing required field: selectData");
        }

        return $this->request('post', '/api/flight/select', $params);
    }


    /**
     * Process Flight Booking
     */
    public function bookFlight(array $validated): array
    {
        $endpoint = '/api/flight/book';

        $payload = [
            'PassengerDetails'   => $validated['PassengerDetails'],
            'BookingItemModels'  => $validated['BookingItemModels'],
            'BookingId'          => $validated['BookingId'],
        ];

        FlightLogger::log("Wakanow Flight Book Request", [
            'endpoint' => $endpoint,
            'passenger_count' => count($payload['PassengerDetails']),
            'booking_id' => $payload['BookingId']
        ]);

        try {
            $response = $this->request('post', $endpoint, $payload);

            FlightLogger::log("Wakanow Flight Book Response", [
                'success' => $response['success'] ?? false,
                'booking_id' => $payload['BookingId']
            ]);

            return $response;

        } catch (\Exception $e) {
            FlightLogger::error("Flight booking error", [
                'error' => $e->getMessage(),
                'booking_id' => $payload['BookingId'] ?? null
            ]);
            throw $e;
        }
    }


    /**
     * Ticket Flight (issue ticket after booking & payment)
     */
    public function ticketFlight(string $bookingId, string $pnrNumber): array
    {
        $endpoint = '/api/flight/ticketpnr';

        $payload = [
            'BookingId' => $bookingId,
            'PnrNumber' => $pnrNumber,
        ];

        FlightLogger::log("Wakanow Flight Ticket Request", $payload);

        try {
            $response = $this->request('post', $endpoint, $payload);

            FlightLogger::log("Wakanow Flight Ticket Response", [
                'booking_id' => $bookingId,
                'pnr_number' => $pnrNumber,
                'status'     => $response['status'] ?? false,
            ]);

            return $response;

        } catch (\Exception $e) {
            FlightLogger::error("Wakanow Flight Ticket API Error", [
                'error' => $e->getMessage(),
                'booking_id' => $bookingId,
                'pnr_number' => $pnrNumber,
            ]);

            throw $e;
        }
    }


    /**
     * Static Data - Airports
     */
    public function getAirports(): array
    {
        try {
            $http = $this->withAuth();
            $endpoint = '/api/flight/airports';
            $url = rtrim($this->baseUrl, '/') . $endpoint;

            FlightLogger::log("Fetching raw airports data", ['url' => $url]);

            $response = $http->get($url);

            FlightLogger::log("Raw airports response received", [
                'status_code' => $response->status(),
                'body_length' => strlen($response->body()),
                'headers' => $response->headers()
            ]);

            return [
                'success' => $response->successful(),
                'status_code' => $response->status(),
                'headers' => $response->headers(),
                'raw_body' => $response->body(),
                'response_object' => $response
            ];

        } catch (Exception $e) {
            FlightLogger::error("Error fetching raw airports", ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'status_code' => 500,
                'headers' => [],
                'raw_body' => null,
                'response_object' => null
            ];
        }
    }


    /**
     * Wallet Balance
     */
    public function walletBalance(): array
    {
        return $this->request('get', '/api/flight/wallet');
    }

    /**
     * Generic request handler with improved error handling and logging
     */
    private function request(string $method, string $endpoint, array $payload = []): array
    {
        try {
            $http = $this->withAuth();
            $url = rtrim($this->baseUrl, '/') . $endpoint;

            FlightLogger::log("Wakanow API Call", [
                'method'  => strtoupper($method),
                'url'     => $url,
                'payload' => $this->sanitizeLogData($payload),
            ]);

            $response = $method === 'post'
                ? $http->post($url, $payload)
                : $http->get($url, $payload);

            FlightLogger::log("Wakanow API Response", [
                'status_code' => $response->status(),
                'headers' => $response->headers(),
            ]);

            if ($response->successful()) {
                $data = $this->parseResponse($response);
                return $this->formatResponse($data, $response->status());
            } else {
                if ($response->status() === 401) {
                    Cache::forget('wakanow_access_token');
                    FlightLogger::error("Authentication failed, token may be expired");
                }

                $errorData = $this->parseResponse($response);
                FlightLogger::error("Wakanow API Error", [
                    'status_code' => $response->status(),
                    'response' => $errorData
                ]);

                return [
                    'success' => false,
                    'message' => $errorData['message'] ?? 'Request failed',
                    'data' => $errorData,
                    'status_code' => $response->status()
                ];
            }

        } catch (Exception $e) {
            FlightLogger::error("Wakanow {$method} error", [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Normalize responses
     */
    private function formatResponse($response, int $statusCode = 200): array
    {
        if (is_array($response) && isset($response[0]) && is_array($response[0]) && isset($response[0]['AirportCode'])) {
            return [
                'success' => true,
                'message' => 'Airports fetched successfully',
                'data'    => $response,
                'status_code' => $statusCode
            ];
        }

        if (isset($response['FlightCombination']) || isset($response['Results'])) {
            return [
                'success' => true,
                'message' => 'Flight search completed successfully',
                'data'    => $response,
                'status_code' => $statusCode
            ];
        }

        if (isset($response['BookingId'])) {
            return [
                'success' => true,
                'message' => 'Flight booked successfully',
                'data'    => $response,
                'status_code' => $statusCode
            ];
        }

        if (isset($response['access_token'])) {
            return [
                'success' => true,
                'message' => 'Authentication successful',
                'data'    => $response,
                'status_code' => $statusCode
            ];
        }

        if (isset($response['status']) && $response['status'] === false) {
            return [
                'success' => false,
                'message' => $response['message'] ?? 'Request failed',
                'data'    => $response['data'] ?? $response,
                'status_code' => $response['status_code'] ?? $statusCode
            ];
        }

        $success = !isset($response['error']) && !isset($response['parse_error']);

        return [
            'success' => $success,
            'message' => $response['message'] ?? ($success ? 'Request successful' : 'Request failed'),
            'data'    => $response,
            'status_code' => $statusCode
        ];
    }

    private function errorResponse(string $errorMessage): array
    {
        return [
            'success' => false,
            'message' => $errorMessage,
            'data'    => [],
            'status_code' => 500
        ];
    }

    /**
     * Remove sensitive data from logs
     */
    private function sanitizeLogData(array $data): array
    {
        $sensitiveFields = ['password', 'token', 'creditCard', 'cvv', 'cardNumber'];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '***';
            }
        }

        return $data;
    }

    /**
     * Parse response handling compressed/encoded data
     */
    private function parseResponse($response): array
    {
        $body = $response->body();

        $json = $response->json();
        if (is_array($json)) {
            return $json;
        }

        $json = json_decode($body, true);
        if (is_array($json)) {
            return $json;
        }

        FlightLogger::error("Failed to parse Wakanow response", [
            'body_sample' => substr($body, 0, 200),
            'length'      => strlen($body)
        ]);

        return [
            'raw_response' => $body,
            'parse_error'  => 'Could not parse response (Base64+Gzip expected)',
        ];
    }


    /**
     * Check wallet balance and lock it (but don't debit yet)
     */
    public function checkAndLockWallet(int $userId, float $amount): array
    {
        return DB::transaction(function () use ($userId, $amount) {
            $wallet = Wallet::where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if (!$wallet) {
                return [
                    'success' => false,
                    'message' => 'Wallet not found for user',
                    'data' => []
                ];
            }

            if ($wallet->amount < $amount) {
                return [
                    'success' => false,
                    'message' => 'Insufficient wallet balance',
                    'data' => [
                        'current_balance' => $wallet->amount,
                        'required' => $amount,
                    ]
                ];
            }

            return [
                'success' => true,
                'message' => 'Wallet has sufficient balance',
                'data' => [
                    'current_balance' => $wallet->balance,
                    'amount_to_debit' => $amount,
                ]
            ];
        });
    }


    /**
     * Debit wallet and create transaction log (called only after successful ticketing)
     */
    public function debitWalletAndCreateLog(int $userId, float $amount, string $bookingId): array
    {
        return DB::transaction(function () use ($userId, $amount, $bookingId) {
            $wallet = Wallet::where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if (!$wallet || $wallet->amount < $amount) {
                return [
                    'success' => false,
                    'message' => 'Wallet check failed during debit',
                ];
            }

            $walletBalanceBefore = $wallet->amount;
            $wallet->decrement('amount', $amount);
            $wallet->refresh();

            $transaction = TransactionLog::create_transaction([
                'service_type' => 'flight_payment',
                'transaction_reference' => $bookingId,
                'amount' => $amount,
                'amount_before' => $walletBalanceBefore,
                'amount_after' => $wallet->amount,
                'status' => 'success',
                'wallet_id' => $wallet->id,
                'provider' => 'wallet',
                'type' => 'debit',
                'description' => "Flight ticket payment for Booking Number $bookingId",
            ]);

            FlightLogger::log("Wallet debited and transaction created", [
                'user_id' => $userId,
                'amount' => $amount,
                'booking_id' => $bookingId,
                'new_balance' => $wallet->amount,
                'transaction_created' => $transaction ? true : false
            ]);

            return [
                'success' => true,
                'message' => 'Wallet debited and transaction logged',
                'data' => [
                    'new_balance' => $wallet->amount,
                    'amount' => $amount,
                ]
            ];
        });
    }

}
