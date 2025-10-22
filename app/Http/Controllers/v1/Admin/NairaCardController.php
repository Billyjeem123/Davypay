<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\NairaCard;
use App\Models\CardTransaction;
use App\Models\TransactionFee;
use App\Models\User;
use App\Services\CardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NairaCardController extends Controller
{
    protected CardService $cardService;

    public function __construct(CardService $cardService)
    {
        $this->cardService = $cardService;
    }

    /**
     * Display all Naira cards
     */
    public function index(Request $request)
    {
        $query = NairaCard::query();

        if ($request->filled('card_status')) {
            $query->where('card_status', $request->card_status);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('brand')) {
            $query->where('brand', $request->brand);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('card_id', 'like', "%{$search}%")
                    ->orWhere('customer_id', 'like', "%{$search}%")
                    ->orWhere('mask', 'like', "%{$search}%")
                    ->orWhere('firstname', 'like', "%{$search}%")
                    ->orWhere('lastname', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $cards = $query->latest()->paginate(20);

        $stats = [
            'total' => NairaCard::count(),
            'active' => NairaCard::where('status', 'active')->count(),
            'inactive' => NairaCard::where('status', 'inactive')->count(),
            'physical' => NairaCard::where('type', 'physical')->count(),
            'virtual' => NairaCard::where('type', 'virtual')->count(),
            'pending' => NairaCard::where('card_status', 'pending')->count(),
            'delivered' => NairaCard::where('card_status', 'delivered')->count(),
            'processing' => NairaCard::where('card_status', 'processing')->count(),
            'failed' => NairaCard::where('card_status', 'failed')->count(),
        ];

        return view('dashboard.card.naira-cards.index', compact('cards', 'stats'));
    }


    /**
     * Show card details
     */
    public function show($id)
    {
        $card = NairaCard::findOrFail($id);

        $transactions = CardTransaction::where('card_id', $card->card_id)
            ->latest()
            ->paginate(10);

        return view('dashboard.card.naira-cards.show', compact('card', 'transactions'));
    }

    /**
     * Update card status (active/inactive)
     */
    public function deactivateCard(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:active,inactive',
        ]);

        try {
            $card = NairaCard::findOrFail($id);
            $oldStatus = $card->status;
            $newStatus = $request->status;

            $this->cardService->updateCardStatus($card->card_id, $newStatus);

            $card->update(['status' => $newStatus]);

            Log::info('Admin updated NairaCard status', [
                'admin_email' => auth()->guard('admin')->user()->email ?? 'unknown',
                'card_id' => $card->card_id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'timestamp' => now()->toDateTimeString(),
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Card status updated successfully.',
                    'new_status' => $newStatus,
                ]);
            }

            return redirect()->route('naira-cards.show', $id)
                ->with('success', 'Card status updated successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to update NairaCard status', [
                'error' => $e->getMessage(),
                'card_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update card status: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Failed to update card status. Please try again.');
        }
    }


    /**
     * Update card Delivery status (pending/processing/delivered/failed)
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:pending,processing,delivered,failed',
        ]);

        try {
            $card = NairaCard::findOrFail($id);

            $card->update(['card_status' => $request->status]);

            Log::info('Admin updated NairaCard delivery status', [
                'admin_email' => auth()->guard('admin')->user()->email ?? 'unknown',
                'card_id' => $card->card_id,
                'old_status' => $card->card_status,
                'new_status' => $request->status,
                'timestamp' => now()->toDateTimeString(),
            ]);

            return redirect()->route('naira-cards.show', $id)
                ->with('success', 'Card delivery status updated successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to update NairaCard delivery status', [
                'error' => $e->getMessage(),
                'card_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Failed to update card delivery status. Please try again.');
        }
    }


    /**
     * Display card transactions
     */
    public function transactions(Request $request)
    {
        $query = CardTransaction::with(['user:id,first_name,last_name,email', 'card']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                    ->orWhere('merchant_name', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $transactions = $query->latest()->paginate(20);

        $stats = [
            'total' => CardTransaction::count(),
            'completed' => CardTransaction::where('status', 'completed')->count(),
            'pending' => CardTransaction::where('status', 'pending')->count(),
            'total_amount' => CardTransaction::where('status', 'completed')->sum('total_amount'),
        ];

        return view('dashboard.card.naira-cards.transactions', compact('transactions', 'stats'));
    }

    /**
     * View card details from API
     */
    public function viewCard($id)
    {
        $card = NairaCard::findOrFail($id);

        try {
            $data = $this->cardService->viewCard($card->card_id);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch card details: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getTransactionFees()
    {
        $fees = TransactionFee::all();
        return view('dashboard.transactions.transaction-fee', compact('fees'));
    }
}
