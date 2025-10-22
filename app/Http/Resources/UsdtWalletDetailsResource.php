<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsdtWalletDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'Usdt Wallet Details',
            'id' => $this->id,
            'address' => $this->address ?? null,
            'balance' => $this->balance ?? null,
            'network' => $this->network ?? null,
            'mode' => $this->mode ?? null,
            'status' => $this->status ?? null,
        ];
    }
}
