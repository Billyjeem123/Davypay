<?php

namespace App\Http\Controllers\v1\Webhook;

use App\Helpers\BillLogger;
use App\Http\Controllers\Controller;
use App\Services\VTpassWebhookService;
use Illuminate\Http\Request;

class VTpassWebhookController  extends Controller
{

    protected $webhookService;

    public function __construct(VTpassWebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    public function processVtPassWebHook(Request $request) {
        try {
            # Get the raw JSON payload from the request body
            $rawPayload = $request->getContent();

            # Log the raw payload for debugging
            BillLogger::log('VTpass Webhook Received (Raw)', [
                'raw_payload' => $rawPayload,
                'all_data' => $request->all()
            ]);

            # Decode the JSON payload
            $validatedData = json_decode($rawPayload, true);

            # Check if JSON decoding was successful
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON payload: ' . json_last_error_msg());
            }

            # Validate that required fields exist
            if (!isset($validatedData['type'])) {
                throw new \Exception('Missing required field: type');
            }

            # Log the decoded payload
            BillLogger::log('VTpass Webhook Decoded', [
                'payload' => $validatedData,
            ]);

            # Process the webhook based on type
            if ($validatedData['type'] === 'transaction-update') {
                if (!isset($validatedData['data'])) {
                    throw new \Exception('Missing required field: data');
                }
                $this->webhookService->handleTransactionUpdate($validatedData['data']);
            }

            # Return 200 OK to acknowledge receipt
            return response()->json(['response' => 'success'], 200);


        } catch (\Exception $e) {
            BillLogger::error('VTpass Webhook Error', [
                'error' => $e->getMessage(),
                'raw_payload' => $request->getContent(),
                'all_data' => $request->all()
            ]);

            # Return 200 to prevent VTpass from retrying
            return response()->json(['response' => 'success'], 200);
        }
    }





}
