<?php

namespace App\Http\Controllers\v1\VirtualCard;

use App\Helpers\Utility;
use App\Helpers\VirtualLogger;
use App\Http\Controllers\Controller;
use App\Http\Requests\GlobalRequest;
use App\Models\Settings;
use App\Services\StrollWalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StrollWalletController extends Controller
{
    protected $strollwallet;

    public function __construct(StrollWalletService $strollwallet)
    {
        $this->strollwallet = $strollwallet;
    }



    /**
     * Create a new card user
     *
     * @param GlobalRequest $request
     * @return JsonResponse
     */


    public function createAccount(GlobalRequest $request): JsonResponse
    {
        try {
            $result = $this->strollwallet->createAccount();

            if ($result['success']) {
                return Utility::outputData(true , 'Card user created successfully', $result['data'],  201);
            }

            return Utility::outputData(true,  $result['message'], [],  $result['status_code']);

        } catch (\Exception $e) {
            return Utility::outputData(false, 'Unable to process request, please try again later', [], 500);
        }
    }


    public function createCard(GlobalRequest $request)
    {
        try {
            $data = $request->validated();
             return $this->strollwallet->createCard($data);

        } catch (\Exception $e) {
            VirtualLogger::log("Error creating card", ['error' => $e->getMessage()]);
            return Utility::outputData(false, 'Unable to process request, please try again later', [Utility::getExceptionDetails($e)], 500);
        }
    }



    public function updateCustomer(GlobalRequest $request): JsonResponse|array
    {
        try {
            $data = $request->validated();
            return $this->strollwallet->updateCardCustomer($data);

        } catch (\Exception $e) {
            VirtualLogger::log("Error creating card", ['error' => $e->getMessage()]);
            return Utility::outputData(false, 'Unable to process request, please try again later', [Utility::getExceptionDetails($e)], 500);
        }
    }

    public function getCustomerData(): JsonResponse
    {
        try {
            $response =  $this->strollwallet->getVirtualCardCustomer();

            return Utility::outputData( $response['success'], $response['message'], $response['data'],  200);
        } catch (\Exception $e) {
            Log::info($e->getMessage());
            return Utility::outputData(false , 'Unable to process request, please try again later', [],  500);
        }
    }

    public function getCardDetails($cardId): JsonResponse
    {
        try {
            $response =  $this->strollwallet->getCardDetails($cardId);

            return Utility::outputData( $response['success'], $response['message'], $response['data'],  200);
        } catch (\Exception $e) {
            Log::info($e->getMessage());
            return Utility::outputData(false , 'Unable to process request, please try again later', [],  500);
        }
    }


    public function getCardTransactions($cardId): JsonResponse
    {
        try {
            $response =  $this->strollwallet->getCardTransactions($cardId);

            return Utility::outputData( $response['success'], $response['message'], $response['data'],  200);
        } catch (\Exception $e) {
            Log::info($e->getMessage());
            return Utility::outputData(false , 'Unable to process request, please try again later', [],  500);
        }
    }


    public function FundWallet(GlobalRequest $request): JsonResponse|array
    {
        try {
            $validated = $request->validated();
            $response = $this->strollwallet->processCardFunding($validated);
            return Utility::outputData( $response['success'], $response['message'], $response['data'] ?? [],  200);

        } catch (\Exception $e) {
            return Utility::outputData(false, 'Unable to process request, please try again later', Utility::getExceptionDetails($e), 500);
        }
    }


    public function getVirtualSettings(): JsonResponse
    {
        $settings = $this->strollwallet->getVirtualSettings();

        return response()->json($settings, 200);
    }

    public function WithdrawFromCard(GlobalRequest $request): JsonResponse|array
    {
        try {
            $validated = $request->validated();
            $response =  $this->strollwallet->processCardWithdrawal($validated);
            return Utility::outputData( $response['success'], $response['message'], $response['data'],  200);

        } catch (\Exception $e) {
            return Utility::outputData(false, 'Unable to process request, please try again later', Utility::getExceptionDetails($e), 500);
        }
    }


    public function freezeUnFreezeCard(GlobalRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $result = $this->strollwallet->processCardUnFreezing($validated);

            return Utility::outputData($result['success'] , $result['message'], [],  200);

        } catch (\Exception $e) {
            return Utility::outputData(false, 'Unable to process request, please try again later', [Utility::getExceptionDetails($e)], 500);
        }
    }


    public function withdrawalStatus(GlobalRequest $request): JsonResponse|array
    {
        try {
            $validated = $request->validated();
            $reference = $validated['reference'];
            $response =  $this->strollwallet->syncCardWithdrawalStatus($reference);
            return Utility::outputData( $response['success'], $response['message'], $response['data'],  200);

        } catch (\Exception $e) {
            return Utility::outputData(false, 'Unable to process request, please try again later', Utility::getExceptionDetails($e), 500);
        }
    }

}
