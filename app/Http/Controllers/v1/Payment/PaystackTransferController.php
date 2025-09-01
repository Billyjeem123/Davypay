<?php

namespace App\Http\Controllers\v1\Payment;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Http\Requests\GlobalRequest;
use App\Models\TransactionLog;
use App\Services\ActivityTracker;
use App\Services\PaystackTransferService;
use GuzzleHttp\Exception\TransferException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PaystackTransferController extends Controller
{
    private $transferService;

    public $tracker;

    public function __construct(PaystackTransferService $transferService,  ActivityTracker $tracker)
    {
        $this->transferService = $transferService;
        $this->tracker = $tracker;
    }

    /**
     * Transfer funds from wallet to bank
     */

    public function transferToBank(GlobalRequest $request): JsonResponse
    {
        $user = Auth::user();
        $validated = $request->validated();

       #  Verify transaction PIN
        if (!$this->verifyTransactionPin($user, $validated['transaction_pin'])) {
            return Utility::outputData(false, 'Invalid transaction PIN', null, 403);
        }

        [$limitOk, $limitMessage] = TransactionLog::checkLimits($user, $validated['amount']);
        if (!$limitOk) {
            return Utility::outputData(false, $limitMessage, [], 403);
        }

        $idempotencyKey = $request->attributes->get('idempotency_key');

        $transferData = [
            'amount' => $validated['amount'],
            'account_number' => $validated['account_number'],
            'bank_code' => $validated['bank_code'],
            'account_name' => $validated['account_name'],
            'bank_name' => $validated['bank_name'] ?? null,
            'narration' => $validated['narration'],
            'idempotency_key' => $idempotencyKey,
        ];
        $result = $this->transferService->transferToBank($user, $transferData);

        $this->tracker->track(
            'initialize_external_bank_transfer',
            "initiated a bank transfer of ₦" . number_format($validated['amount']) . " to {$validated['account_name']}  at {$validated['bank_name']}",
            [
                'user_id' => $user->id,
                'amount' => $validated['amount'],
                'account_number' => $validated['account_number'],
                'account_name' => $validated['account_name'],
                'bank_code' => $validated['bank_code'],
                'bank_name' => $validated['bank_name'] ?? null,
                'narration' => $validated['narration'],
                'ip' => request()->ip(),
                'provider' => 'external_bank_transfer',
                'status' => 'initiated',
            ]
        );

        return Utility::outputData(
            $result['success'],
            $result['message'] ?? ($result['success'] ? 'Transfer successful' : 'Transfer failed'),
            $result['data'] ?? null,
            $result['success'] ? 200 : 400
        );
    }
    /**
     * Get list of supported banks
     */
    public function getBanks(): JsonResponse
    {
        $banks = $this->transferService->getBanks();

        return response()->json([
            'success' => true,
            'message' => 'Banks retrieved successfully',
            'data' => $banks['data'] ?? []
        ]);
    }

    /**
     * Resolve account number to get account name
     */
    public function resolveAccount(GlobalRequest $request)
    {
        $validated = $request->validated();

        #  Return the JsonResponse directly
        return $this->transferService->resolveAccountNumber(
            $validated['account_number'],
            $validated['bank_code']
        );
    }

    /**
     * Verify user's transaction PIN
     */
    private function verifyTransactionPin($user, string $pin): bool
    {
       #  Implement your PIN verification logic here
       #  This could be hashed PIN comparison
        return password_verify($pin, $user->pin);
    }

    public function verifyTransferStatus($reference)
    {
        return $this->transferService->verifyTransfer($reference);

    }


}
