<?php

namespace App\Http\Controllers\v1\Payment;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PreferredPaymentController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $provider = Utility::getSetting('preferred_provider');
            return Utility::outputData(true, 'Fetched successfully', $provider, 200);
        } catch (\Throwable $e) {
            Log::error('Failed to fetch preferred provider', [
                'error' => $e->getMessage(),
            ]);
            return Utility::outputData(false, 'Failed to fetch preferred provider', null, 500);
        }
    }

}
