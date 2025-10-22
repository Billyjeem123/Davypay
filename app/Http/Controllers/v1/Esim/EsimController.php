<?php

namespace App\Http\Controllers\v1\Esim;

use App\Http\Controllers\Controller;
use App\Http\Requests\GlobalRequest;
use App\Models\Esim;
use App\Models\Settings;
use App\Services\EsimService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EsimController extends Controller
{
    public EsimService $esim;

    public function __construct(EsimService $esim)
    {

        return $this->esim = $esim;

    }

    /**
     * Authenticate and generate bearer token for Sotel eSIM API
     *
     * @param Request $request
     * @return array
     */
    public function authenticate(Request $request): array
    {
        $response = $this->esim->authenticate();
        return $response;
    }

    /**
     * Health check endpoint
     *
     * @return JsonResponse
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Sotel eSIM API is running',
            'timestamp' => now()
        ]);
    }




    /**
     * Get data plans from Sotel API
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getDataPlans(GlobalRequest $request): JsonResponse
    {

         $validated = $request->validated();

        try {
            $plans = $this->esim->getDataPlans($validated['country'], $validated['type']);

            return response()->json([
                'success' => true,
                'data' => $plans,
                'filters' => [
                    'country' => $validated['country'],
                    'type' => $validated['type']
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch data plans: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 400);
        }
    }


    /**
     * Create/Provision a new eSIM
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createEsim(GlobalRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $response = $this->esim->createEsim($validated);

        return response()->json($response, $response['status_code'] ?? 200);
    }

    public function ActivateEsim($iccid): JsonResponse
    {
        try {
            $qrCode = $this->esim->activateSim($iccid);

            return response()->json([
                'success' => true,
                'data' => $qrCode,
                'message' => 'QR Code retrieved successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve QR Code: ' . $e->getMessage(),
            ], 400);
        }
    }

    public function getICCIDProfile($iccid): JsonResponse
    {
        try {
            $profile = $this->esim->getEuiccProfile($iccid);

            return response()->json([
                'success' => true,
                'data' => $profile,
                'message' => 'euICC profile fetched successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch euICC profile: ' . $e->getMessage(),
            ], 400);
        }
    }


    public function getDataUsage($iccid): JsonResponse
    {
        try {
            $profile = $this->esim->getDataUsage($iccid);

            return response()->json([
                'success' => true,
                'data' => $profile,
                'message' => 'euICC usage fetched successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get euICC data usage: ' . $e->getMessage(),
            ], 400);
        }
    }


    public function getCountries(Request $request)
    {
        $result = $this->esim->getCountries(
            request()->query('page', 0),
            request()->query('size', 15)
        );

        return response()->json($result);
    }

    public function myEsims(): JsonResponse
    {
        try {

            $plans = $this->esim->UserEsims();

            return response()->json([
                'success' => true,
                'message'    => "Fetched user sims",
                "data"  => $plans
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch eSIMs: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get eSIM markup and dollar conversion rate.
     */
    public function getEsimSettings(): JsonResponse
    {
        $markupPercentage = (float) Settings::get('esim_markup_percentage', 0);
        $dollarRate = (float) Settings::get('dollar_conversion_rate', 1400);

        return response()->json([
            'success' => true,
            'data' => [
                'esim_markup_percentage' => $markupPercentage,
                'dollar_conversion_rate' => $dollarRate
            ]
        ], 200);

}

}
