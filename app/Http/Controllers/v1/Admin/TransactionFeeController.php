<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeliveryFeeRequest;
use App\Http\Requests\TransactionDepositFee;
use App\Http\Requests\TransactionFeeRequest;
use App\Http\Requests\UpdateDeliveryFeeRequest;
use App\Http\Requests\UpdateTransactionFeeRequest;
use App\Models\DeliveryFee;
use App\Models\TransactionFee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

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

    /**
     * Store/Edit Transaction Fees
     * @param TransactionDepositFee $request
     * @return RedirectResponse
     * @throws Throwable
     */
    public function saveTransactionFee(TransactionDepositFee $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();

            TransactionFee::updateOrCreate(
                [
                    'provider' => $validated['provider'],
                    'type'     => $validated['type']
                ],
                [
                    'min' => $validated['min'] ?? 0,
                    'max' => $validated['max'] ?? 0,
                    'fee' => $validated['fee'] ?? 0,
                ]
            );

            DB::commit();
            return redirect()->back()->with('success', 'Transaction fee saved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to save fee: ' . $e->getMessage());
        }
    }

    /**
     * Store Delivery Fees
     * @param DeliveryFeeRequest $request
     * @return RedirectResponse
     * @throws Throwable
     */
    public function saveDeliveryFee(DeliveryFeeRequest $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();

            DeliveryFee::updateOrCreate(
                [
                    'state' => $validated['state'],
                    'amount'     => $validated['amount']
                ]
            );

            DB::commit();
            return redirect()->back()->with('success', 'Delivery fee saved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to save fee: ' . $e->getMessage());
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
    public function updateDeliveryFee(UpdateDeliveryFeeRequest $request)
    {
        try {
            $validated = $request->validated();
            $fee = DeliveryFee::findOrFail($validated['id']);
            $fee->update($validated);

            return redirect()->back()->with('success', 'Delivery fee updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'An error occurred while updating the delivery fee: ' . $e->getMessage());
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
    public function deleteDeliveryFee($id)
    {
        try {
            $fee = DeliveryFee::findOrFail($id);
            $fee->delete();

            return redirect()->back()->with('success', 'Delivery fee deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete delivery fee.');
        }
    }





}
