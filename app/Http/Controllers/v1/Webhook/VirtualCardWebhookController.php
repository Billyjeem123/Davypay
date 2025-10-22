<?php

namespace App\Http\Controllers\v1\Webhook;

use App\Helpers\VirtualLogger;
use App\Http\Controllers\Controller;
use App\Models\VirtualCard;
use App\Services\CardWebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VirtualCardWebhookController extends Controller
{
    protected CardWebhookService $cardWebhookService;

    public function __construct(CardWebhookService $cardWebhookService)
    {
        $this->cardWebhookService = $cardWebhookService;
    }

    public function handle(Request $request)
    {
        $payload = $request->all();
        VirtualLogger::log('Webhook received:', $payload);

        if (isset($payload['authorization.request'])) {
            return $this->handleNairaAuthorization($payload);
        }

        if (isset($payload['transaction.created'])) {
            return $this->handleNairaTransactionCreated($payload);
        }

        if (isset($payload['transaction.refund'])) {
            return $this->handleNairaTransactionRefund($payload);
        }

        $event = $payload['event'] ?? null;
        switch ($event) {
            case 'virtualcard.created.success':
                $this->handleCardCreated($payload);
                break;

            case 'virtualcard.transaction.declined':
                $this->handleTransactionDeclined($payload);
                break;

            case 'virtualcard.transaction.crossborder':
                $this->handleCrossBorder($payload);
                break;

            case 'virtualcard.transaction.declined.terminated':
                $this->handleCardTerminated($payload);
                break;

            default:
                Log::warning("Unhandled event type", ['event' => $event, 'payload' => $payload]);
                break;
        }

        return response()->json(['status' => 'received'], 200);
    }

    protected function handleNairaAuthorization(array $payload)
    {
        try {
            Log::info('Naira Card Authorization Request', ['payload' => $payload]);
            VirtualLogger::log('Naira Card Authorization Request:', $payload);

            $result = $this->cardWebhookService->handleAuthorization($payload);
            return response()->json($result, 200);
        } catch (\Exception $e) {
            Log::error('Naira Card Authorization Error', [
                'error' => $e->getMessage(),
                'payload' => $payload
            ]);
            return response()->json(['APPROVE' => 'NO', 'reason' => 'System error'], 200);
        }
    }

    protected function handleNairaTransactionCreated(array $payload)
    {
        try {
            Log::info('Naira Transaction Created', ['payload' => $payload]);
            VirtualLogger::log('Naira Transaction Created:', $payload);

            $result = $this->cardWebhookService->handleTransactionCreated($payload);
            return response()->json($result, 200);
        } catch (\Exception $e) {
            Log::error('Naira Transaction Created Error', [
                'error' => $e->getMessage(),
                'payload' => $payload
            ]);
            return response()->json(['success' => false, 'message' => 'Error processing transaction'], 200);
        }
    }

    protected function handleNairaTransactionRefund(array $payload)
    {
        try {
            Log::info('Naira Transaction Refund', ['payload' => $payload]);
            VirtualLogger::log('Naira Transaction Refund:', $payload);

            $result = $this->cardWebhookService->handleTransactionRefund($payload);
            return response()->json($result, 200);
        } catch (\Exception $e) {
            Log::error('Naira Transaction Refund Error', [
                'error' => $e->getMessage(),
                'payload' => $payload
            ]);
            return response()->json(['success' => false, 'message' => 'Error processing refund'], 200);
        }
    }
    protected function handleCardCreated(array $data)
    {
        VirtualCard::updateOrCreate(
            ['id' => $data['id']],
            [
                'reference' => $data['reference'],
                'status' => $data['status'],
                'company_id' => $data['companyId'],
                'created_status' => $data['createdStatus']
            ]
        );
    }

    protected function handleTransactionDeclined(array $data)
    {
        // Handle declined transaction
    }

    protected function handleCrossBorder(array $data)
    {
        // Log or sync fee-related data
    }

    protected function handleCardTerminated(array $data)
    {
        // Update card as terminated in DB
    }
}
