<?php

namespace App\Http\Controllers\v1\VirtualCard;

use App\Helpers\Utility;
use App\Helpers\VirtualLogger;
use App\Http\Controllers\Controller;
use App\Http\Requests\GlobalRequest;
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


    public function createAccountAndCard(GlobalRequest $request)
    {
        try {
            $result = $this->strollwallet->createCardUser();

            return Utility::outputData( $result, $result['message'], $result['data'], 200);

        } catch (\Exception $e) {
            VirtualLogger::log("Error creating card", ['error' => $e->getMessage()]);
            return Utility::outputData(false, 'Unable to process request, please try again later', [Utility::getExceptionDetails($e)], 500);
        }
    }


    public function getCustomerData(): JsonResponse
    {
        try {
            $response =  $this->strollwallet->getVirtualCardCustomer();

            return Utility::outputData( $response['success'], $response['message'], $response['data'],  400);
        } catch (\Exception $e) {
            Log::info($e->getMessage());
            return Utility::outputData(false , 'Unable to process request, please try again later', [],  500);
        }
    }

    public function getCardDetails($cardId): JsonResponse
    {
        try {
            $response =  $this->strollwallet->getCardDetails($cardId);

            return Utility::outputData( $response['success'], $response['message'], $response['data'],  400);
        } catch (\Exception $e) {
            Log::info($e->getMessage());
            return Utility::outputData(false , 'Unable to process request, please try again later', [],  500);
        }
    }


    public function getCardTransactions($cardId): JsonResponse
    {
        try {
            $response =  $this->strollwallet->getCardTransactions($cardId);

            return Utility::outputData( $response['success'], $response['message'], $response['data'],  400);
        } catch (\Exception $e) {
            Log::info($e->getMessage());
            return Utility::outputData(false , 'Unable to process request, please try again later', [],  500);
        }
    }





    public function FundWallet(GlobalRequest $request): JsonResponse|array
    {
        try {
            $validated = $request->validated();
           return $this->strollwallet->processCardFunding($validated);

        } catch (\Exception $e) {
            return Utility::outputData(false, 'Unable to process request, please try again later', Utility::getExceptionDetails($e), 500);
        }
    }


    public function WithdrawFromCard(GlobalRequest $request): JsonResponse|array
    {
        try {
            $validated = $request->validated();
            return $this->strollwallet->processCardWithdrawal($validated);

        } catch (\Exception $e) {
            return Utility::outputData(false, 'Unable to process request, please try again later', Utility::getExceptionDetails($e), 500);
        }
    }


}
