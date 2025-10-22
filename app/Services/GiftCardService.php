<?php

namespace App\Services;

use App\Models\GiftCard;
use App\Models\GiftCardList;
use App\Models\TransactionLog;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class GiftCardService
{
    public function listSellableCards(array $filters = []): Collection|array
    {
        return GiftCardList::query()
            ->when(!empty($filters['search']), fn($q) => $q->where('name', 'like', '%' . $filters['search'] . '%'))
            ->get();
    }

    public function store(array $data): GiftCardList
    {
        if (isset($data['logo'])) {
            $data['logo_path'] = $data['logo']->store('giftcard-logos', 'public');
        }

        return GiftCardList::create([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'logo_path'   => $data['logo_path'] ?? null,
            'status'      => $data['status'] ?? 'active',
        ]);
    }

    /**
     * Update a gift card list item.
     */
    public function update(GiftCardList $giftCardList, array $data): GiftCardList
    {
        if (isset($data['logo'])) {
            $data['logo_path'] = $data['logo']->store('giftcard-logos', 'public');
        }

        $giftCardList->update([
            'name'        => $data['name'] ?? $giftCardList->name,
            'description' => $data['description'] ?? $giftCardList->description,
            'logo_path'   => $data['logo_path'] ?? $giftCardList->logo_path,
            'status'      => $data['status'] ?? $giftCardList->status,
        ]);

        return $giftCardList;
    }

    /**
     * Delete a gift card list item.
     */
    public function delete(GiftCardList $giftCardList): bool
    {
        return $giftCardList->delete();
    }

    /**
     * Sell a Gift card.
     */
    public function createGiftCard(array $data, $user): GiftCard
    {
        if (isset($data['image'])) {
            $path = $data['image']->store('giftcards', 'public');
            $data['image_path'] = $path;
        }

        return GiftCard::create([
            'user_id'        => $user->id,
            'type_id'        => $data['type_id'],
            'country'        => $data['country'],
            'initial_value'  => $data['amount'],
            'current_value'  => $data['amount'],
            'code'           => $data['code'] ?? null,
            'image_path'     => $data['image_path'] ?? null,
            'currency'       => $data['currency'] ?? 'USD',
            'status'         => 'pending',
        ]);
    }

    /**
     * Get all gift cards belonging to a user
     */
    public function getUserGiftCards($user, array $filters = []): Collection|array
    {
        $query = GiftCard::with('type')
            ->where('user_id', $user->id);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest()->get();
    }


    public function evaluateGiftCard(GiftCard $giftCard, float $rate = 0.8): GiftCard
    {
        $giftCard->evaluated_value = $giftCard->amount * $rate;
        $giftCard->save();

        return $giftCard;
    }

    public function confirmPayment(GiftCard $giftCard): GiftCard
    {
        DB::transaction(function () use ($giftCard) {
            $giftCard->update(['status' => 'paid']);

            $user = $giftCard->user;

            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();

            if (!$wallet) {
                throw new \Exception("Wallet not found for user {$user->id}");
            }

            $walletBalanceBefore = $wallet->amount;
            $wallet->amount += $giftCard->evaluated_value;
            $wallet->save();

            TransactionLog::create_transaction([
                'service_type'    => 'giftcard',
                'amount'          => $giftCard->evaluated_value,
                'amount_before'   => $walletBalanceBefore,
                'amount_after'    => $wallet->amount,
                'status'          => 'success',
                'wallet_id'       => $wallet->id,
                'provider'        => 'system',
                'type'            => 'credit',
                'description'     => "Sold GiftCard evaluated at {$giftCard->evaluated_value}",
            ]);
        });

        return $giftCard->fresh();
    }
}
