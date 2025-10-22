<?php

namespace App\Http\Controllers\v1\Webhook;

use App\Http\Controllers\Controller;
use App\Services\CardWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CardWebhookController extends Controller
{
    protected CardWebhookService $service;

    public function __construct(CardWebhookService $service)
    {
        $this->service = $service;
    }

    /**
     * Unified webhook handler - automatically detects webhook type
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();

            if (isset($payload['authorization.request'])) {
                Log::info('Card Authorization Request', ['payload' => $payload]);
                $result = $this->service->handleAuthorization($payload);
                return response()->json($result, 200);
            }

            if (isset($payload['transaction.created'])) {
                Log::info('Transaction Created', ['payload' => $payload]);
                $result = $this->service->handleTransactionCreated($payload);
                return response()->json($result, 200);
            }

            if (isset($payload['transaction.refund'])) {
                Log::info('Transaction Refund', ['payload' => $payload]);
                $result = $this->service->handleTransactionRefund($payload);
                return response()->json($result, 200);
            }

            Log::warning('Unknown webhook type', ['payload' => $payload]);
            return response()->json(['success' => false, 'message' => 'Unknown webhook type'], 200);

        } catch (\Exception $e) {
            Log::error('Webhook Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all()
            ]);

            if (isset($request->all()['authorization.request'])) {
                return response()->json(['APPROVE' => 'NO', 'reason' => 'System error'], 200);
            }

            return response()->json(['success' => false, 'message' => 'System error'], 200);
        }
    }

    public function handleAuthorization(Request $request): JsonResponse
    {
        try {
            Log::info('Card Authorization Request', ['payload' => $request->all()]);
            $result = $this->service->handleAuthorization($request->all());
            return response()->json($result, 200);
        } catch (\Exception $e) {
            Log::error('Card Authorization Error', ['error' => $e->getMessage(), 'payload' => $request->all()]);
            return response()->json(['APPROVE' => 'NO', 'reason' => 'System error'], 200);
        }
    }

    public function handleTransactionCreated(Request $request): JsonResponse
    {
        try {
            Log::info('Transaction Created', ['payload' => $request->all()]);
            $result = $this->service->handleTransactionCreated($request->all());
            return response()->json($result, 200);
        } catch (\Exception $e) {
            Log::error('Transaction Created Error', ['error' => $e->getMessage(), 'payload' => $request->all()]);
            return response()->json(['success' => false, 'message' => 'Error processing transaction'], 200);
        }
    }

    public function handleTransactionRefund(Request $request): JsonResponse
    {
        try {
            Log::info('Transaction Refund', ['payload' => $request->all()]);
            $result = $this->service->handleTransactionRefund($request->all());
            return response()->json($result, 200);
        } catch (\Exception $e) {
            Log::error('Transaction Refund Error', ['error' => $e->getMessage(), 'payload' => $request->all()]);
            return response()->json(['success' => false, 'message' => 'Error processing refund'], 200);
        }
    }
}
