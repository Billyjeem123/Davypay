<?php

namespace App\Http\Controllers\v1\Transaction;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Http\Requests\GlobalRequest;
use App\Http\Resources\UserTransactionResource;
use App\Services\ActivityTracker;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class TransactionController extends Controller
{
    protected TransactionService $transactionService;

    protected ActivityTracker $tracker;
    public function __construct(TransactionService $transactionService, ActivityTracker $activityTracker)
    {
        $this->transactionService = $transactionService;
        $this->tracker = $activityTracker;
    }


    public function myTransactionHistory(GlobalRequest $request, $id = null): JsonResponse
    {
        try {
               $validatedData = $request->validated();
               $filters = $validatedData;

            if ($id) {
                $transaction = $this->transactionService->getUserTransactionById($id);
                if (!$transaction) {
                    return Utility::outputData(false, "Transaction not found", null, 404);
                }
                return Utility::outputData(true, "Transaction retrieved successfully", new UserTransactionResource($transaction), 200);
            }

            $transactions = $this->transactionService->getAllUserTransactions($filters);

            $this->tracker->track('transaction_history', "viewed transaction history", [
                "effective" => true,
            ]);
            return Utility::outputData(true, "Transactions retrieved successfully", [
                'data' => UserTransactionResource::collection($transactions['data']),
                'pagination' => $transactions['pagination']
            ], 200);

        } catch (Throwable $e) {
            Log::error("Error fetching transactions: " . $e->getMessage());
            return Utility::outputData(false, "Unable to process request, please try again later", [], 500);
        }
    }


    public function recentTransfers(GlobalRequest $request): JsonResponse
    {
        try {
            $transactions = $this->transactionService->getRecentExternalBankTransfers();
            return Utility::outputData(true, "Recent transfers retrieved successfully", [
                'data' => ($transactions['data']),
            ], 200);

        } catch (Throwable $e) {
            Log::error("Error fetching recent transfers: " . $e->getMessage());
            return Utility::outputData(false, "Unable to process request, please try again later", [], 500);
        }
    }


    public function sendTransactionHistoryPdf(GlobalRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $filters = $validatedData;
            $user = Auth::user();

            $transactions = $this->transactionService->getAllUserTransactions($filters, false); // Get all without pagination

            if (empty($transactions)) {
                return Utility::outputData(false, "No transactions found with the specified filters", null, 404);
            }

            $pdfPath = $this->generateTransactionPdf($transactions, $user, $filters);
            $this->sendTransactionPdfEmail($user, $pdfPath, $filters);

            if (file_exists($pdfPath)) {
                unlink($pdfPath);
            }

            $this->tracker->track('transaction_pdf_sent', "sent transaction history PDF via email", [
                "effective" => true,
                "filters_applied" => !empty($filters)
            ]);

            return Utility::outputData(true, "Transaction history PDF sent to your email successfully", null, 200);

        } catch (Throwable $e) {
            Log::error("Error sending transaction PDF: " . $e->getMessage());
            return Utility::outputData(false, "Unable to send PDF, please try again later", [], 500);
        }
    }



    private function generateTransactionPdf($transactions, $user, $filters): string
    {
        $html = view('pdfs.transaction-history', [
            'transactions' => $transactions,
            'user' => $user,
            'filters' => $filters,
            'generated_at' => now()->format('Y-m-d H:i:s')
        ])->render();

        $pdf = app('dompdf.wrapper');
        $pdf->loadHTML($html);
        $pdf->setPaper('A4', 'portrait');

        $filename = 'transaction_history_' . $user->id . '_' . time() . '.pdf';
        $pdfPath = storage_path('app/temp/' . $filename);

        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        file_put_contents($pdfPath, $pdf->output());

        return $pdfPath;
    }

    private function sendTransactionPdfEmail($user, $pdfPath, $filters): void
    {
        $filterSummary = $this->buildFilterSummary($filters);

        Mail::send('email.transaction-history-pdf', [
            'user' => $user,
            'filter_summary' => $filterSummary,
            'generated_at' => now()->format('F j, Y \a\t g:i A')
        ], function ($message) use ($user, $pdfPath) {
            $message->to($user->email, $user->name)
                ->subject('Your Transaction History Report')
                ->attach($pdfPath, [
                    'as' => 'transaction_history_' . date('Y-m-d') . '.pdf',
                    'mime' => 'application/pdf'
                ]);
        });
    }


    private function buildFilterSummary($filters): string
    {
        $summary = [];

        if (!empty($filters['service_type'])) {
            $summary[] = "Service Type: " . $filters['service_type'];
        }
        if (!empty($filters['status'])) {
            $summary[] = "Status: " . ucfirst($filters['status']);
        }
        if (!empty($filters['start_date'])) {
            $summary[] = "From: " . $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $summary[] = "To: " . $filters['end_date'];
        }
        if (!empty($filters['reference'])) {
            $summary[] = "Reference: " . $filters['reference'];
        }

        if (!empty($filters['amount'])) {
            $summary[] = "Amount: " . number_format($filters['amount'], 2);
        }
        if (!empty($filters['amount_before'])) {
            $summary[] = "Amount Before: " . number_format($filters['amount_before'], 2);
        }
        if (!empty($filters['amount_after'])) {
            $summary[] = "Amount After: " . number_format($filters['amount_after'], 2);
        }

        return !empty($summary) ? implode(', ', $summary) : 'No filters applied';
    }


}
