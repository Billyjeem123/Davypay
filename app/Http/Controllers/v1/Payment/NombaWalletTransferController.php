<?php

namespace App\Http\Controllers\v1\Payment;

use App\Helpers\PaymentLogger;
use App\Http\Controllers\Controller;
use App\Http\Requests\GlobalRequest;
use App\Http\Requests\InitializeNombaTransferRequest;
use App\Services\NombaWalletTransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NombaWalletTransferController extends Controller
{
    protected $nombaTransferService;

    public function __construct(NombaWalletTransferService $nombaTransferService)
    {
        $this->nombaTransferService = $nombaTransferService;
    }

    /**
     * Initialize wallet-to-wallet transfer
     */
    public function initializeWalletTransfer(InitializeNombaTransferRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $result = $this->nombaTransferService->initializeTransfer($validated);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Transfer initialized successfully',
                    'data' => $result['data']
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['error']
                ], $result['status'] ?? 400);
            }

        } catch (\Exception $e) {
            PaymentLogger::error('Controller exception in nomba  transfer initialization', [
                'message' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Transfer initialization failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get transfer status
     */
    public function getTransferStatus(string $reference): JsonResponse
    {
        try {
            $result = $this->nombaTransferService->getTransferStatus($reference);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Transfer status retrieved successfully',
                    'data' => $result['data']
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to retrieve transfer status',
                    'error' => $result['error']
                ], $result['status'] ?? 404);
            }

        } catch (\Exception $e) {
            Log::error('Controller exception in transfer status retrieval', [
                'message' => $e->getMessage(),
                'reference' => $reference,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve transfer status',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get user's transfer history
     */
    public function getTransferHistory(): JsonResponse
    {
        try {
            $result = $this->nombaTransferService->getTransferHistory();

            return response()->json([
                'success' => true,
                'message' => 'Transfer history retrieved successfully',
                'data' => $result['data'],
                'pagination' => $result['pagination'] ?? null
            ], 200);

        } catch (\Exception $e) {
            Log::error('Controller exception in transfer history retrieval', [
                'message' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve transfer history',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
