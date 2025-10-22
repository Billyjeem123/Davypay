<?php

namespace App\Http\Controllers\v1\VirtualCard;

use App\Exceptions\StroUsdtException;
use App\Helpers\Utility;
use App\Helpers\UsdtLogger;
use App\Http\Controllers\Controller;
use App\Models\EpinProvider;
use App\Models\EpinRate;
use App\Services\StroUsdtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Str;

class StroUsdtController extends Controller
{
    protected StroUsdtService $strowallet;

    public function __construct(StroUsdtService $strowallet)
    {
        $this->strowallet = $strowallet;
    }


    /**
     * Generate a new USDT address for the authenticated user.
     * @param Request $request
     * @return JsonResponse
     */
    public function generateUsdtAddress(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $addressResponse = $this->strowallet->createUsdtAddress($userId);

            UsdtLogger::log("Strowallet API Response", ['response' => $addressResponse]);

            if (!is_array($addressResponse)) {
                return Utility::outputData(false, 'Invalid response from payment provider', [], 500);
            }

            $message = $addressResponse['message'] ?? 'Address generated successfully';
            $data = $addressResponse['data'] ?? $addressResponse;
            $isSuccess = $this->isResponseSuccessful($addressResponse);

            if (!$isSuccess) {
                return Utility::outputData(false, $message, [], 400);
            }

            return Utility::outputData(true, $message, $data, 200);

        } catch (StroUsdtException $e) {
            UsdtLogger::log("StroUsdt Exception", [
                'error' => $e->getMessage(),
                'context' => $e->getContext()
            ]);
            return Utility::outputData(false, $e->getMessage(), [], $e->getCode() ?: 500);

        } catch (\Exception $e) {
            UsdtLogger::log("Error creating USDT address", ['error' => $e->getMessage()]);
            return Utility::outputData(false, 'Unable to process request, please try again later', [], 500);
        }
    }


    /**
     * Get USDT transaction history for the authenticated user.
     * @param Request $request
     * @return JsonResponse
     */
    public function getUsdtHistory(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $historyResponse = $this->strowallet->getUsdtHistory($userId);

            $message = $historyResponse['message'] ?? 'History retrieved successfully';
            $data = $historyResponse['data'] ?? [];
            $isSuccess = $historyResponse['success'] ?? false;

            if (!$isSuccess) {
                return Utility::outputData(false, $message, [], 400);
            }

            return Utility::outputData(true, $message, $data, 200);

        } catch (StroUsdtException $e) {
            UsdtLogger::log("StroUsdt Exception", [
                'error' => $e->getMessage(),
                'context' => $e->getContext()
            ]);
            return Utility::outputData(false, $e->getMessage(), [], $e->getCode() ?: 500);

        } catch (\Exception $e) {
            UsdtLogger::log("Error fetching USDT history", ['error' => $e->getMessage()]);
            return Utility::outputData(false, 'Unable to fetch USDT history, please try again later', [], 500);
        }
    }


    /**
     * Send USDT to a specified address.
     * @param Request $request
     * @return JsonResponse
     */
    public function sendUsdt(Request $request): JsonResponse
    {
        $request->validate([
            'to_address' => 'required|string',
            'amount' => 'required|numeric|min:0.0001',
        ]);

        try {
            $toAddress = $request->input('to_address');
            $amount = (float)$request->input('amount');

            $sendResponse = $this->strowallet->sendUsdt($toAddress, $amount);

            UsdtLogger::log("Strowallet Send USDT Response", ['response' => $sendResponse]);

            if (!is_array($sendResponse)) {
                return Utility::outputData(false, 'Invalid response from payment provider', [], 500);
            }

            $message = $sendResponse['message'] ?? 'USDT sent successfully';
            $data = $sendResponse['data'] ?? $sendResponse;
            $isSuccess = $this->isResponseSuccessful($sendResponse);

            if (!$isSuccess) {
                return Utility::outputData(false, $message, [], 400);
            }

            return Utility::outputData(true, $message, $data, 200);

        } catch (StroUsdtException $e) {
            UsdtLogger::log("StroUsdt Exception", [
                'error' => $e->getMessage(),
                'context' => $e->getContext()
            ]);
            return Utility::outputData(false, $e->getMessage(), [], $e->getCode() ?: 500);

        } catch (\Exception $e) {
            UsdtLogger::log("Error sending USDT", ['error' => $e->getMessage()]);
            return Utility::outputData(false, 'Unable to process USDT transaction, please try again later', [], 500);
        }
    }

    public function handle(Request $request): JsonResponse
    {
        try {
            $result = $this->strowallet->process($request->all());

            return Utility::outputData(
                $result['success'],
                $result['message'],
                $result['data'] ?? [],
                $result['success'] ? 200 : 400
            );

        } catch (StroUsdtException $e) {
            UsdtLogger::log("Handle Request Exception", [
                'error' => $e->getMessage(),
                'context' => $e->getContext()
            ]);
            return Utility::outputData(false, $e->getMessage(), [], $e->getCode() ?: 500);

        } catch (\Exception $e) {
            UsdtLogger::log("Error handling request", ['error' => $e->getMessage()]);
            return Utility::outputData(false, 'Unable to process request, please try again later', [], 500);
        }
    }


    /**
     * Get exchange rate between USDT and NGN.
     * @param Request $request
     * @return JsonResponse
     */
    public function getExchangeRate(Request $request): JsonResponse
    {
        try {
            $from = strtoupper($request->input('from', 'USD'));
            $to   = strtoupper($request->input('to', 'NGN'));

            if (!$from || !$to) {
                $payload = json_decode($request->getContent(), true);
                if (is_array($payload)) {
                    $from = strtoupper($payload['from'] ?? 'USD');
                    $to   = strtoupper($payload['to'] ?? 'NGN');
                }
            }

            $rateResponse = $this->strowallet->getExchangeRate($from, $to);

            if ($rateResponse['success'] && isset($rateResponse['data']['rate'])) {
                $rateResponse['data']['rate'] = number_format((float) $rateResponse['data']['rate'], 8, '.', '');
            }

            return Utility::outputData(
                $rateResponse['success'],
                $rateResponse['message'],
                $rateResponse['data'],
                $rateResponse['success'] ? 200 : 400
            );

        } catch (\Exception $e) {
            UsdtLogger::log("Unexpected Exchange Rate Error", ['error' => $e->getMessage()]);
            return Utility::outputData(false, 'Unable to fetch exchange rate', [], 500);
        }
    }





    /**
     * Convert currency between USDT and NGN.
     * @param Request $request
     * @return JsonResponse
     */
    public function convert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'direction' => 'required|in:USDT_TO_NGN,NGN_TO_USDT',
        ]);

        try {
            $result = $this->strowallet->convertCurrency(
                $request->user()->id,
                $validated['amount'],
                $validated['direction']
            );

            return Utility::outputData(
                $result['success'],
                $result['message'],
                $result['data'],
                $result['success'] ? 200 : 400
            );

        } catch (StroUsdtException $e) {
            UsdtLogger::log("Currency Conversion Exception", [
                'error' => $e->getMessage(),
                'context' => $e->getContext()
            ]);
            return Utility::outputData(false, $e->getMessage(), [], $e->getCode() ?: 500);

        } catch (\Exception $e) {
            UsdtLogger::log("Error converting currency", ['error' => $e->getMessage()]);
            return Utility::outputData(false, 'Conversion failed', [], 500);
        }
    }


    /**
     * Fund virtual card from USDT balance.
     * @param Request $request
     * @return JsonResponse
     * @throws \Throwable
     */
    public function fundFromUsdt(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        try {
            $result = $this->strowallet->fundFromUsdt($request->input('amount'));

            return Utility::outputData(
                $result['success'],
                $result['message'],
                $result['data'],
                $result['success'] ? 200 : 400
            );

        } catch (StroUsdtException $e) {
            UsdtLogger::log("Fund From USDT Exception", [
                'error' => $e->getMessage(),
                'context' => $e->getContext()
            ]);
            return Utility::outputData(false, $e->getMessage(), [], $e->getCode() ?: 500);

        } catch (\Exception $e) {
            UsdtLogger::log("Error funding from USDT", ['error' => $e->getMessage()]);
            return Utility::outputData(false, 'Unable to fund virtual card, please try again later', [], 500);
        }
    }

    public function buy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'card_network' => 'required|string|in:MTN,Glo,Airtel,9mobile',
            'value'        => 'required|numeric|min:50',
            'quantity'     => 'required|integer|min:1|max:50',
        ]);

        $user     = $request->user();
        $network  = $validated['card_network'];
        $value    = $validated['value'];
        $quantity = $validated['quantity'];

        $rate = EpinRate::getRate($network, $value, $quantity);

        if (!$rate) {
            return response()->json([
                'success' => false,
                'message' => "No rate configured for {$network} {$value} with quantity {$quantity}",
            ], 400);
        }

        $amountToDebit = $rate * $quantity;

        $wallet = $user->wallet;
        if (!$wallet || $wallet->amount < $amountToDebit) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient wallet balance',
            ], 400);
        }

        try {
            $response = $this->strowallet->buyEpin($network, $value, $quantity);

            if (!empty($response['data']['cards'])) {
                $trxid = $response['data']['trxid'] ?? Str::uuid();

                $walletResult = $this->strowallet->debitWalletAndCreateLog($user->id, $amountToDebit, $trxid);
                if (!$walletResult['success']) {
                    return response()->json($walletResult, 400);
                }

                $emailSent = $this->strowallet->sendEpinEmail($user, $response['data']);

                return response()->json([
                    'success' => true,
                    'message' => 'Epin purchased successfully',
                    'details' => [
                        'transaction_id' => $trxid,
                        'rate_per_unit'  => $rate,
                        'total_amount'   => $amountToDebit,
                        'cards_count'    => count($response['data']['cards']),
                        'email_sent'     => $emailSent,
                        'is_sandbox'     => !($response['success'] ?? false),
                        'api_message'    => $response['message'] ?? null
                    ],
                    'data' => $response['data'],
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => $response['message'] ?? 'Epin purchase failed - no cards received',
                'data'    => $response['data'] ?? [],
            ], 400);

        } catch (\Throwable $e) {
            Log::error("Error Buying Epin", [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to buy Epin, please try again later',
            ], 500);
        }
    }


    /**
     * Get all PDF files for the authenticated user
     */
    public function getMyPdfs(Request $request): JsonResponse
    {
        $user = $request->user();

        try {
            $pdfs = $this->strowallet->getUserPdfFiles($user->id);

            return response()->json([
                'success' => true,
                'message' => count($pdfs) > 0 ? 'PDFs found' : 'No PDFs found for this user',
                'data' => $pdfs,
                'count' => count($pdfs)
            ]);

        } catch (\Throwable $e) {
            Log::error("Error getting user PDFs", [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch PDF files',
            ], 500);
        }
    }

    /**
     * Download PDF by filename (user must own the file)
     */
    public function downloadByFilename(Request $request, string $filename): mixed
    {
        $user = $request->user();

        try {
            if (!preg_match("/^epin_receipt_{$user->id}_(.+)\.pdf$/", $filename)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found or access denied'
                ], 404);
            }

            $filePath = "epins/{$filename}";

            if (!Storage::exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found'
                ], 404);
            }

            return Storage::download($filePath, $filename, [
                'Content-Type' => 'application/pdf'
            ]);

        } catch (\Throwable $e) {
            Log::error("Error downloading PDF by filename", [
                'user_id' => $user->id,
                'filename' => $filename,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to download file',
            ], 500);
        }
    }


    /**
     * Get all providers
     */
    public function getProviders(Request $request): JsonResponse
    {
        $user = $request->user();

        try {
            $providers = EpinProvider::all();

            return response()->json([
                'success' => true,
                'message' => 'Providers fetched successfully',
                'data' => $providers,
            ]);

        } catch (\Throwable $e) {
            Log::error("Error getting providers", [
                'user_id' => $user?->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch providers',
            ], 500);
        }
    }


    /**
     * Determine if the API response indicates success
     */
    private function isResponseSuccessful(array $response): bool
    {
        if (isset($response['success'])) {
            return (bool)$response['success'];
        }

        if (isset($response['status'])) {
            return $response['status'] === 'success' || $response['status'] === true;
        }

        if (isset($response['error'])) {
            return !$response['error'];
        }

        return true;
    }
}
