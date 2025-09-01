<?php

namespace App\Http\Controllers\v1\Payment;

use App\Helpers\PaymentLogger;
use App\Http\Controllers\Controller;

use App\Http\Requests\InitializeNombaPaymentRequest;
use App\Http\Requests\NombaTransferRequest;
use App\Services\NombaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NombaController extends Controller
{
    protected $nombaService;

    public function __construct(NombaService $nombaService)
    {
        $this->nombaService = $nombaService;
    }

    /**
     * Test authentication endpoint
     *
     * @return JsonResponse
     */
    public function testAuthentication(): JsonResponse
    {
        $result = $this->nombaService->testAuthentication();

        return response()->json($result, $result['success'] ? 200 : 401);
    }

    /**
     * Get current token info (for debugging)
     *
     * @return JsonResponse
     */
    public function getTokenInfo(): JsonResponse
    {
        $result = $this->nombaService->getTokenInfo();

        return response()->json($result);
    }




    /**
     * Initialize payment to fund wallet
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function initializePayment(InitializeNombaPaymentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->nombaService->initializePayment($validated);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Payment initialized successfully',
                'data' => $result['data']
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize payment',
                'error' => $result['error']
            ], $result['status'] ?? 500);
        }
    }

    /**
     * Verify payment status
     *
     * @param string $orderReference
     * @return JsonResponse
     */
    public function nombaCallback(Request $request): JsonResponse
    {
        $orderId = $request->query('orderId');
        $orderReference = $request->query('orderReference');

        // Optionally log or validate
        PaymentLogger::log("Nomba Callback Received", [
            'orderId' => $orderId,
            'orderReference' => $orderReference,
        ]);
        $result = $this->nombaService->verifyPayment($orderId);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify payment',
                'error' => $result
            ], $result['status'] ?? 500);
        }
    }

    /**
     * Cancel payment
     *
     * @param string $orderReference
     * @return JsonResponse
     */
    public function cancelPayment(string $orderReference): JsonResponse
    {
        $result = $this->nombaService->cancelPayment($orderReference);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Payment canceled successfully',
                'data' => $result['data']
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel payment',
                'error' => $result['error']
            ], $result['status'] ?? 500);
        }
    }


    public function getBanks()
    {
        return response()->json(
            $this->nombaService->getAllBanks()
        );
    }

    public function resolveAccount(Request $request)
    {
        $data = $request->all();
        return response()->json(
            $this->nombaService->resolveAccountNumber($data)
        );
    }





}
