<?php

namespace App\Http\Controllers\v1\VirtualCard;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateNairaCardRequest;
use App\Services\CardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CardController extends Controller
{
    protected CardService $cardService;

    public function __construct(CardService $cardService)
    {
        $this->cardService = $cardService;
    }

    /**
     * Create a new Naira card for the authenticated user
     */
    public function createNairaCard(CreateNairaCardRequest $request)
    {
        $validated = $request->validated();
        $user = $request->user();

        try {
            $this->cardService->createNairaCardUser($user);

            $result = $this->cardService->createNairaCard(
                $user,
                $validated['type'],
                $validated['brand'],
                $validated['address'] ?? [],
                $validated['number'] ?? null
            );

            return response()->json($result, 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'trace'   => config('app.debug') ? $e->getTrace() : [],
            ], 400);
        }
    }

    /**
     * View the physical card for the authenticated user.
     */
    public function viewPhysicalCard(Request $request): JsonResponse
    {
        $user = $request->user();
        $card = $user->physical_card;

        if (!$card) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No physical card found for this user.'
            ], 404);
        }

        try {
            $data = $this->cardService->viewCard($card->card_id);

            return response()->json([
                'status' => 'success',
                'data'   => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * View the history of physical card for the authenticated user.
     */
    public function viewCardHistory(Request $request): JsonResponse
    {
        $user = $request->user();
        $card = $user->physical_card;

        if (!$card) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No physical card found for this user.'
            ], 404);
        }

        try {
            $data = $this->cardService->viewCardHistory($card->card_id);

            return response()->json([
                'status' => 'success',
                'data'   => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change the pin of physical card for the authenticated user.
     */
    public function changePin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'old_pin' => 'required|string|size:4',
            'new_pin' => 'required|string|size:4',
        ]);

        $user = $request->user();
        $card = $user->physical_card;

        if (!$card) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No physical card found for this user.'
            ], 404);
        }

        try {
            $data = $this->cardService->changePin(
                $card->card_id,
                $validated['old_pin'],
                $validated['new_pin']
            );

            return response()->json([
                'status' => 'success',
                'message' => 'PIN changed successfully',
                'data'   => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset the pin of the physical card for the authenticated user.
     */
    public function resetPin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'new_pin' => 'required|string|size:4',
        ]);

        $user = $request->user();
        $card = $user->physical_card;

        if (!$card) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No physical card found for this user.'
            ], 404);
        }

        try {
            $data = $this->cardService->resetPin(
                $card->card_id,
                $validated['new_pin']
            );

            return response()->json([
                'status' => 'success',
                'message' => 'PIN changed successfully',
                'data'   => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enable 2FA for the physical card for the authenticated user.
     */
    public function  enable2fa(Request $request): JsonResponse
    {
        $user = $request->user();
        $card = $user->physical_card;

        if (!$card) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No physical card found for this user.'
            ], 404);
        }

        try {
            $data = $this->cardService->enable2fa($card->card_id);

            return response()->json([
                'status' => 'success',
                'data'   => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * create Dispute for the physical card for the authenticated user.
     */
    public function createDispute(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|in:other,canceled,not_received,fraudulent,duplicate,product_not_as_described,service_not_as_described',
            'explanation' => 'required|string|min:10',
            'transaction_id' => 'required|string',
        ]);

        try {
            $data = $this->cardService->createDispute(
                $validated['reason'],
                $validated['explanation'],
                $validated['transaction_id']
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Dispute created successfully',
                'data'   => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update card status (activate/deactivate)
     */
    public function updateCardStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:active,inactive',
        ]);

        $user = $request->user();
        $card = $user->physical_card;

        if (!$card) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No physical card found for this user.'
            ], 404);
        }

        try {
            $data = $this->cardService->updateCardStatus(
                $card->card_id,
                $validated['status']
            );

            $card->update(['status' => $validated['status']]);

            return response()->json([
                'status' => 'success',
                'message' => "Card {$validated['status']} successfully",
                'data'   => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store delivery details for the authenticated user's card.
     */
    public function updateAddress(CreateNairaCardRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $card = $user->physical_card;

            if (!$card) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No card found for this user'
                ], 404);
            }

            $card->update($request->validated());

            return response()->json([
                'status'  => 'success',
                'message' => 'Delivery details updated successfully',
                'data'    => $card
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Get charges and delivery fees for a card.
     */
    public function getFees(Request $request): JsonResponse
    {
        try
        {
        $data = $this->cardService->getTransactionFees($request->input('type', 'physical_card'), $request->input('state', 'lagos'));

            return response()->json([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }



}
