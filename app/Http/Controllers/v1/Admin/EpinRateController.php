<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\EpinRate;
use Illuminate\Http\Request;

class EpinRateController extends Controller
{

    /**
     * List all rates
     */
    public function index()
    {
        $epinRates = EpinRate::all();
        return view('dashboard.transactions.epin-rates', compact('epinRates'));
    }

    /**
     * Store or update an E-pinRate
     */
    public function storeOrUpdate(Request $request)
    {
        try {
            $validated = $request->validate([
                'card_network' => 'required|string|in:MTN,GLO,AIRTEL,9MOBILE',
                'value' => 'required|numeric|min:50',
                'min_quantity' => 'required|integer|min:1',
                'max_quantity' => 'nullable|integer|gte:min_quantity',
                'rate' => 'required|numeric|min:1',
            ]);

            EpinRate::updateOrCreate(
                [
                    'card_network' => $validated['card_network'],
                    'value' => $validated['value'],
                    'min_quantity' => $validated['min_quantity'],
                    'max_quantity' => $validated['max_quantity'],
                ],
                [
                    'rate' => $validated['rate'],
                ]
            );
            return redirect()->back()->with('success', 'Epin Rates Store successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to store Epin Rates.');
        }
    }

    /**
     * Delete a rate
     */
    public function destroy($id)
    {
        try {
            $rate = EpinRate::findOrFail($id);
            $rate->delete();

            return redirect()->back()->with('success', 'Epin Rates deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete Epin Rates.');
        }
    }
}
