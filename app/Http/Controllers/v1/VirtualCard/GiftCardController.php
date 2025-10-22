<?php

namespace App\Http\Controllers\v1\VirtualCard;

use App\Http\Controllers\Controller;
use App\Models\GiftCard;
use App\Models\GiftCardList;
use App\Services\GiftCardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GiftCardController extends Controller
{
    protected GiftCardService $service;

    public function __construct(GiftCardService $service)
    {
        $this->service = $service;
    }

    /** List sellable cards
     */
    public function index(Request $request)
    {
        $cards = $this->service->listSellableCards($request->all());
        return response()->json($cards);
    }

    /**
     * List sellable cards for auth user
     */
    public function myList(Request $request)
    {
        $filters = $request->only(['status']);

        $cards = $this->service->getUserGiftCards($request->user(), $filters);

        return response()->json([
            'status' => true,
            'data'   => $cards,
        ]);
    }



    /**
     * Create a gift card sale
     */
    public function sell(Request $request)
    {
        $validated = $request->validate([
            'type_id'   => 'required|exists:gift_card_lists,id',
            'country'   => 'required|string|max:100',
            'amount'    => 'required|numeric|min:1',
            'image'     => 'required|file|mimes:jpg,jpeg,png|max:2048',
            'code'      => 'required|string|max:255',
        ]);

        $giftCard = $this->service->createGiftCard($validated, $request->user());

        return response()->json([
            'status'  => true,
            'message' => 'Gift card submitted for evaluation.',
            'data'    => $giftCard,
        ], 201);
    }


    /**
     * Store a new gift card list item.
     */
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'logo' => 'nullable|image|mimes:jpg,jpeg,png',
                'status' => 'nullable|in:active,inactive',
            ]);

            $this->service->store($data);

            return redirect()->back()->with('success', 'GiftCard type stored successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to store GiftCard type.');
        }
    }

    /**
     * Update a gift card list item.
     */
    public function update(Request $request, GiftCardList $giftCardList)
    {
        try {
            $data = $request->validate([
                'name'        => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'logo'        => 'nullable|image|mimes:jpg,jpeg,png',
                'status'      => 'nullable|in:active,inactive',
            ]);

          $this->service->update($giftCardList, $data);

            return redirect()->back()->with('success', 'GiftCard type updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update GiftCard type. ' . $e->getMessage());
        }
    }


    /** Evaluate card
     * @param int $id
     */
    public function evaluate($id, Request $request)
    {
        $request->validate([
            'rate' => 'required|numeric|min:0',
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        $giftCard = GiftCard::findOrFail($id);

        $giftCard->amount = $request->amount;
        $giftCard = $this->service->evaluateGiftCard($giftCard, $request->rate);

        if ($request->has('notes')) {
            $giftCard->notes = $request->notes;
            $giftCard->save();
        }

        return redirect()->back()->with('success', 'Gift card evaluated successfully');
    }

    /** Confirm and pay a user
     * @param int $id
     */
    public function confirm($id)
    {
        $giftCard = GiftCard::findOrFail($id);
        $giftCard = $this->service->confirmPayment($giftCard);
        return response()->json($giftCard);
    }

    /**
     * Delete a gift card list item.
     */
    public function destroy(GiftCardList $giftCardList): JsonResponse
    {
        $this->service->delete($giftCardList);

        return response()->json([
            'status' => true,
            'message' => 'Gift card list item deleted successfully.',
        ]);
    }
}
