<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AirtimeToCashResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $amount = floatval($this->amount);
        $adminRate = floatval(optional($this->network)->admin_rate);
        $expectedReturn = $amount - ($amount * $adminRate / 100);

        return [
            'type' => 'airtime to cash',
            'id' => $this->id,
            'attributes' => [
                'status' => $this->status,
                'rate' => $adminRate."%",
                'amount' => $this->amount,
                'expected_return' => number_format($expectedReturn, 2, '.', ''),
                'message' => $this->message,
                'file' => $this->file,
                'network' => $this->network->network_name,
                'is_completed' => $this->is_completed,
            ]
        ];
    }

}
