<?php

namespace App\Http\Controllers\v1\Admin;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNetworkProviderRequest;
use App\Http\Requests\SubmitAirtimeRequest;
use App\Http\Requests\UpdateNetworkProviderRequest;
use App\Http\Resources\AirtimeToCashResource;
use App\Models\AirtimeToCash;
use App\Models\NetworkProvider;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class NetworkProviderController extends Controller
{
    /**
     * Display a listing of the network providers.
     */


    public function index()
    {
        $networkProviders = NetworkProvider::orderBy('network_name')->get();
        return view('dashboard.transactions.airtime-to-cash', compact('networkProviders'));
    }


    public function allRecords()
    {
        $transfers = AirtimeToCash::with(['network', 'user'])->orderBy('id', 'desc')->get();
//        return  response()->json([$transfers]);
        return view('dashboard.transactions.airtime-to-cash-records', compact('transfers'));
    }

    /**
     * Store a newly created network provider.
     */

    public function store(StoreNetworkProviderRequest $request)
    {
        try {
            $validated = $request->validated();

            NetworkProvider::create([
                'network_name'     => $validated['network_name'],
                'admin_rate'       => $validated['admin_rate'],
                'transfer_number'  => $validated['transfer_number'],
                'is_active'        => true,
            ]);

            return redirect()->back()->with('success', 'Network provider added successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to add network provider. Please try again.');
        }
    }


    /**
     * Update the specified network provider.
     */

    public function update(UpdateNetworkProviderRequest $request, $id)
    {
        $provider = NetworkProvider::findOrFail($id);

        try {
            $validated = $request->validated();

            $provider->update([
                'network_name' => $validated['network_name'],
                'admin_rate' => $validated['admin_rate'],
                'transfer_number' => $validated['transfer_number'],
            ]);

            return redirect()->back()->with('success', 'Network provider updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update network provider. Please try again.');
        }


    }


    /**
     * Toggle the active status of the network provider.
     */
    public function toggle($id)
    {
        try {
            $provider = NetworkProvider::findOrFail($id);
            $provider->update(['is_active' => !$provider->is_active]);

            $status = $provider->is_active ? 'activated' : 'deactivated';
            return redirect()->back()->with('success', "Network provider {$status} successfully!");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update provider status. Please try again.');
        }
    }

    /**
     * Remove the specified network provider.
     */
    public function destroy($id)
    {
        try {
            $provider = NetworkProvider::findOrFail($id);
            $provider->delete();
            return redirect()->back()->with('success', 'Network provider deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete network provider. Please try again.');
        }
    }

    /**
     * Get active network providers for API/AJAX calls
     */
    public function getActiveProviders(): \Illuminate\Http\JsonResponse
    {
        try {
            $providers = NetworkProvider::active();
            return response()->json([
                'success' => true,
                'data' => $providers->map(function($provider) {
                    return [
                        'id' => $provider->id,
                        'network_name' => $provider->network_name,
                        'image' => $provider->image,
                        'admin_rate' => $provider->admin_rate,
                        'transfer_number' => $provider->formatted_number,
                        'calculate_amount' => function($amount) use ($provider) {
                            return $provider->calculateUserAmount($amount);
                        }
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch network providers'
            ], 500);
        }
    }

    /**
     * Calculate total amount including admin rate
     */
    public function calculateAmount(Request $request)
    {
        $request->validate([
            'provider_id' => 'required|exists:network_providers,id',
            'amount' => 'required|numeric|min:50|max:10000'
        ]);

        try {
            $provider = NetworkProvider::findOrFail($request->provider_id);
            $totalAmount = $provider->calculateUserAmount($request->amount);

            return response()->json([
                'success' => true,
                'data' => [
                    'airtime_amount' => $request->amount,
                    'admin_charge' => ($request->amount * $provider->admin_rate) / 100,
                    'total_amount' => $totalAmount,
                    'admin_rate' => $provider->admin_rate,
                    'transfer_number' => $provider->formatted_number
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate amount'
            ], 500);
        }
    }


    public function submitRequest(SubmitAirtimeRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $validated = $request->validated();
            $airtimeRequest = AirtimeToCash::create([
                'user_id' => Auth::id(),
                'message' => $validated['message'] ?? null,
                'amount' => $validated['amount'] ?? null,
                'file' => $validated['file'],
                'network_provider_id' => $validated['network_provider_id'],
                'status' => 'pending',
            ]);


            $user = auth()->user();
            if (!$this->verifyTransactionPin($user, $validated['transaction_pin'])) {
                return Utility::outputData(false , "Invalid transaction PIN", [], 200);
            }

           // $this->sendAdminNotification($airtimeRequest);

            return response()->json([
                'success' => true,
                'message' => 'Airtime to cash request submitted successfully',
                'data' => [
                    'id' => $airtimeRequest->id,
                    'status' => $airtimeRequest->status,
                    'network_provider' => $airtimeRequest->network_provider,
                    'amount' => $airtimeRequest->amount,
                    'file' => $airtimeRequest->file,
                    'created_at' => $airtimeRequest->created_at,
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit request',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Send notification email to admin
     */
    private function sendAdminNotification($airtimeRequest)
    {
        $user = User::find($airtimeRequest->user_id);
        $adminEmail = config('mail.admin_email', 'admin@yourapp.com');

        Mail::to($adminEmail)->send(new AirtimeToCashNotification($airtimeRequest, $user));
    }


    /**
     * Get user's airtime to cash requests
     */
    public function userRequests()
    {
        try {
            $requests = AirtimeToCash::where('user_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->get();

            return Utility::outputData(true, "Request fetched successfully", AirtimeToCashResource::collection($requests), 200);
        } catch (\Exception $e) {
            \Log::error('Error fetching user requests: ' . $e->getMessage());

            return Utility::outputData(false, "Failed to fetch requests. Please try again later.", [], 500);
        }
    }


    // Add these methods to your NetworkProviderController class

    /**
     * Approve a transfer
     */
    public function approveTransfer($id)
    {
        try {
            $transfer = AirtimeToCash::findOrFail($id);

            $transfer->update([
                'status' => 'completed',
                'is_completed' => true,
            ]);

            return redirect()->back()->with('success', 'Transfer approved successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to approve transfer. Please try again.');
        }
    }

    /**
     * Reject a transfer
     */
    public function rejectTransfer($id)
    {
        try {
            $transfer = AirtimeToCash::findOrFail($id);

            $transfer->update([
                'status' => 'failed',
                'is_completed' => false,
            ]);

            return redirect()->back()->with('success', 'Transfer rejected successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to reject transfer. Please try again.');
        }
    }

    /**
     * Delete a transfer
     */
    public function deleteTransfer($id)
    {
        try {
            $transfer = AirtimeToCash::findOrFail($id);

            if ($transfer->file && Storage::exists($transfer->file)) {
                Storage::delete($transfer->file);
            }

            $transfer->delete();

            return redirect()->back()->with('success', 'Transfer deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete transfer. Please try again.');
        }
    }


    /**
     * Verify user's transaction PIN
     */
    private function verifyTransactionPin($user, string $pin): bool
    {
        return password_verify($pin, $user->pin);
    }


}
