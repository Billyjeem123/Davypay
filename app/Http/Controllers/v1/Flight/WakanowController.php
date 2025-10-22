<?php

namespace App\Http\Controllers\v1\Flight;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookFlightRequest;
use App\Models\Airport;
use App\Models\TransactionLog;
use App\Models\Wallet;
use App\Services\WakanowService;
use App\Helpers\FlightLogger;
use App\Helpers\Utility;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class WakanowController extends Controller
{
    protected WakanowService $wakanow;

    public function __construct(WakanowService $wakanow)
    {
        $this->wakanow = $wakanow;
    }

    /**
     * Get airport list with search and limit
     */
    public function airports(): JsonResponse
    {
        try {
            $search = request()->query('search');

            $query = Airport::query();

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('country', 'LIKE', "%{$search}%")
                        ->orWhere('city', 'LIKE', "%{$search}%")
                        ->orWhere('city_country', 'LIKE', "%{$search}%");
                });
            }

            $airports = $query->limit(100)->get();

            if ($airports->isEmpty()) {
                return $this->handleApiResponse([
                    'success' => false,
                    'message' => 'No airport found',
                    'data' => [],
                    'status_code' => 404
                ], 'Airport not found');
            }

            return $this->handleApiResponse([
                'success' => true,
                'message' => 'Airport retrieved',
                'data' => $airports,
                'status_code' => 200
            ], 'Airport retrieved');

        } catch (\Exception $e) {
            FlightLogger::error("Error fetching airport list", ['error' => $e->getMessage()]);
            return Utility::outputData(false, 'Unable to fetch airport list', [], 500);
        }
    }


    /**
     * Flight Search
     */
    public function searchFlights(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'adults' => 'integer|min:1|max:6',
                'children' => 'integer|min:0|max:4',
                'infants' => 'integer|min:0|max:4',
                'cabinClass' => 'in:F,C,W,Y',
                'currency' => 'in:NGN,GHS',
                'itineraries' => 'required|array|min:1',
                'itineraries.*.departure' => 'required|string|size:3',
                'itineraries.*.destination' => 'required|string|size:3',
                'itineraries.*.departureDate' => 'required|date|after_or_equal:today',
            ]);

            $params = array_merge([
                'adults' => 1,
                'children' => 0,
                'infants' => 0,
                'cabinClass' => 'Y',
                'currency' => 'NGN',
            ], $validated);

            $searchType = match (count($params['itineraries'])) {
                1 => "OneWay",
                2 => "Return",
                default => "Multidestination",
            };

            $itineraries = [];
            foreach ($params['itineraries'] as $itinerary) {
                $itineraries[] = [
                    "Departure" => strtoupper($itinerary['departure']),
                    "Destination" => strtoupper($itinerary['destination']),
                    "DepartureDate" => Carbon::parse($itinerary['departureDate'])->format("m/d/Y"),
                    "Ticketclass" => $params['cabinClass'],
                ];
            }

            $payload = [
                "FlightSearchType" => $searchType,
                "Ticketclass" => $params['cabinClass'],
                "Adults" => (int)$params['adults'],
                "Children" => (int)$params['children'],
                "Infants" => (int)$params['infants'],
                "TargetCurrency" => $params['currency'],
                "Itineraries" => $itineraries
            ];

            FlightLogger::log("Flight search payload", ['payload' => $payload]);

            $response = $this->wakanow->searchFlights($payload);

            // 🔥 Log raw API response
            FlightLogger::log("Wakanow Raw Flight Search Response", [
                'raw_response' => $response
            ]);

            // Add extras safely
            $response['search_type'] = $searchType;
            $response['flight_count'] = (isset($response['data'])
                && is_array($response['data'])
                && array_is_list($response['data']))
                ? count($response['data'])
                : 0;

            return $this->handleApiResponse($response, 'Flight search completed');

        } catch (ValidationException $e) {
            return $this->handleApiResponse([
                'success' => false,
                'message' => 'Validation failed',
                'data'    => $e->errors(),
                'status_code' => 422,
            ], 'Validation failed');

        } catch (\Exception $e) {
            FlightLogger::error("Flight search error", [
                'error' => $e->getMessage(),
                'input' => $request->all()
            ]);

            return $this->handleApiResponse([
                'success' => false,
                'message' => 'Unable to search flights',
                'data'    => ['error' => $e->getMessage()],
                'status_code' => 500,
            ], 'Unable to search flights');
        }
    }


    /**
     * Select a flight for pricing details
     */
    public function selectFlight(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'selectData' => 'required|string',
                'currency' => 'sometimes|in:NGN,GHS',
            ]);

            $payload = [
                'selectData' => $validated['selectData'],
                'TargetCurrency' => $validated['currency'] ?? 'NGN'
            ];

            FlightLogger::log("Flight selection request", [
                'payload' => $payload,
                'user_id' => auth()->id()
            ]);

            $response = $this->wakanow->selectFlight($payload);

            return $this->handleApiResponse($response, 'Flight selection completed');

        } catch (ValidationException $e) {
            return Utility::outputData(false, 'Validation failed', $e->errors(), 422);

        } catch (\Exception $e) {
            FlightLogger::error("Flight selection error", [
                'error' => $e->getMessage(),
                'input' => $request->all()
            ]);
            return Utility::outputData(false, 'Unable to select flight', [], 500);
        }
    }

    public function bookFlight(BookFlightRequest $request): JsonResponse
    {
        try {
            $response = $this->wakanow->bookFlight($request->validated());

            return $this->handleApiResponse($response, 'Flight booking completed');

        } catch (\Throwable $e) {
            FlightLogger::error("Flight booking error", [
                'error' => $e->getMessage(),
                'booking_id' => $request->input('booking_id')
            ]);
            return Utility::outputData(false, 'Unable to book flight', [], 500);
        }
    }

    /**
     * Issue ticket for booked flight
     */
    public function ticketFlight(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'BookingId' => 'required|string',
                'PnrNumber' => 'required|string',
                'Amount' => 'required|numeric|min:1',
            ]);

            $userId = auth()->id();
            $amount = $validated['Amount'];
            $bookingId = $validated['BookingId'];
            $pnrNumber = $validated['PnrNumber'];

            $walletCheck = $this->wakanow->checkAndLockWallet($userId, $amount);
            if (!$walletCheck['success']) {
                return response()->json($walletCheck, 400);
            }

            $response = $this->wakanow->ticketFlight($bookingId, $pnrNumber);

            $pnrStatus = $response['data']['FlightBookingSummary']['PnrStatus'] ?? null;
            $ticketStatus = $response['data']['FlightBookingSummary']['TicketStatus'] ?? null;

            if ($pnrStatus === 'Confirmed Pnr' && $ticketStatus === 'Success') {
                $debitResult = $this->wakanow->debitWalletAndCreateLog($userId, $amount, $bookingId);

                FlightLogger::log("Flight ticketing successful - wallet debited", [
                    'booking_id' => $bookingId,
                    'pnr_status' => $pnrStatus,
                    'ticket_status' => $ticketStatus,
                    'debit_success' => $debitResult['success'] ?? false
                ]);

            } else {
                FlightLogger::warning("Flight ticketing failed - no charges made", [
                    'booking_id' => $bookingId,
                    'pnr_status' => $pnrStatus,
                    'ticket_status' => $ticketStatus
                ]);
            }

            return response()->json($response, $response['status_code'] ?? 200);

        } catch (\Exception $e) {
            FlightLogger::error("Flight ticketing error", [
                'error' => $e->getMessage(),
                'payload' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to issue ticket',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get wallet balance
     */
    public
    function walletBalance(): JsonResponse
    {
        try {
            $response = $this->wakanow->walletBalance();

            return $this->handleApiResponse($response, 'Wallet balance retrieved');

        } catch (\Exception $e) {
            FlightLogger::error("Error fetching wallet balance", ['error' => $e->getMessage()]);
            return Utility::outputData(false, 'Unable to fetch wallet balance', [], 500);
        }
    }


    /**
     * Unified API response handler
     */
    private function handleApiResponse(array $response, string $defaultMessage): JsonResponse
    {
        $statusCode = $response['status_code'] ?? (($response['success'] ?? false) ? 200 : 400);

        // Detect if data is a real list of results or just an error object
        $hasData = isset($response['data'])
            && is_array($response['data'])
            && array_is_list($response['data'])
            && !empty($response['data']);

        $dataCount = ($hasData) ? count($response['data']) : 0;

        FlightLogger::log("API Response", [
            'success' => $response['success'] ?? false,
            'message' => $response['message'] ?? $defaultMessage,
            'status_code' => $statusCode,
            'has_data' => $hasData,
            'data_count' => $dataCount
        ]);

        // Base response
        $output = [
            'success'     => $response['success'] ?? false,
            'message'     => $response['message'] ?? $defaultMessage,
            'data'        => $response['data'] ?? [],
            'status_code' => $statusCode,
        ];

        // Merge any extra keys (like search_type, flight_count)
        $extras = collect($response)->except(['success','message','data','status_code'])->all();
        $output = array_merge($output, $extras);

        return response()->json($output, $statusCode);
    }



    /**
     * Get airports list (raw debug)
     */
    public function airportsRaw(): JsonResponse
    {
        try {
            $response = $this->wakanow->getAirports();

            return Utility::outputData(
                $response['success'],
                $response['message'],
                $response['data'] ?? [],
                $response['status_code'] ?? ($response['success'] ? 200 : 400)
            );

        } catch (\Exception $e) {
            FlightLogger::error("Error fetching airports (raw)", ['error' => $e->getMessage()]);
            return Utility::outputData(false, 'Unable to fetch airports list', [], 500);
        }
    }

}
