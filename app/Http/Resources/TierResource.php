<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TierResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */


    public function toArray(Request $request): array
    {
        return [
            'type' => 'Tiers',
            'id' => $this->id,
            'attributes' => [
                'name' => $this->name,
                'daily_limit' => $this->daily_limit,
                'maximum_balance' => $this->getFormattedMaxBalance(),
            ]
        ];
    }

    private function getFormattedMaxBalance()
    {
        // Ensure you check the exact string or use case-insensitive check if needed
        if (strtolower($this->name) === 'tier_3') {
            return 'unlimited';
        }

        // For others, if maximum_balance is null, you can return null or some fallback string like 'N/A'
        return $this->wallet_balance;
    }

}
