<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionDepositFee;
use App\Http\Requests\TransactionFeeRequest;
use App\Http\Requests\UpdateTransactionFeeRequest;
use App\Models\TransactionFee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionFeeController extends Controller
{

    public function saveTransferFee(TransactionFeeRequest $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            $now = now();

            # Prepare Transfer Ranges only
            $transfers = collect($validated['transfer_min'] ?? [])->map(function ($min, $index) use ($validated, $now) {
                return [
                    'type' => $validated['type'],
                    'min' => $min,
                    'max' => $validated['transfer_max'][$index] ?? 0,
                    'fee' => $validated['transfer_percent'][$index] ?? 0,
                    'provider' => $validated['provider'],
                ];
            });

            # Insert transfer ranges using the model
            TransactionFee::insert($transfers->toArray());

            DB::commit();
            return redirect()->back()->with('success', 'Transfer fees added successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to save transfer fees: ' . $e->getMessage());
        }
    }



    public function saveTransferFeeDeposit(TransactionDepositFee $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            $now = now();

            # Prepare Transfer Ranges only
            $transfers = collect($validated['deposit_min'] ?? [])->map(function ($min, $index) use ($validated, $now) {
                return [
                    'type' => "deposit",
                    'min' => $min,
                    'max' => $validated['deposit_max'][$index] ?? 0,
                    'fee' => $validated['deposit_platform_fee'][$index] ?? 0,
                    'provider' => $validated['provider'],
                ];
            });

            # Insert transfer ranges using the model
            TransactionFee::insert($transfers->toArray());

            DB::commit();
            return redirect()->back()->with('success', 'Transfer fees added successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to save transfer fees: ' . $e->getMessage());
        }
    }

    public function PaymentConfiguration(){

        $stats = TransactionFee::all();
        return view('dashboard.transactions.gateway-configuaration', $stats);
    }


    public function updateTransactionFee(UpdateTransactionFeeRequest $request)
    {
        try {
            $validated = $request->validated();
            $fee = TransactionFee::findOrFail($validated['id']);
            $fee->update($validated);

            return redirect()->back()->with('success', 'Transaction fee updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'An error occurred while updating the transaction fee: ' . $e->getMessage());
        }
    }

    public function deleteFee($id)
    {
        try {
            $fee = TransactionFee::findOrFail($id);
            $fee->delete();

            return redirect()->back()->with('success', 'Transaction fee deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete transaction fee.');
        }
    }





}
