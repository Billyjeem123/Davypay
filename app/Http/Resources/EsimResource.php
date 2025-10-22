<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EsimResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'esim',
            'id'   => $this->id,
            'attributes' => [
                'user_id'             => $this->user_id,
                'sim_id'              => $this->sim_id,
                'iccid'               => $this->iccid,
                'product_id'          => $this->product_id,
                'imsi'                => $this->imsi,
                'state'               => $this->state,
                'last_operation_date' => $this->last_operation_date,
                'activation_code'     => $this->activation_code,
                'smdp'                => $this->smdp,
                'purchase_date'       => $this->purchase_date,
                'plan_product_id'     => $this->plan_product_id,
                'plan_name'           => $this->plan_name,
                'data_usage_allowance'=> $this->data_usage_allowance,
                'time_allowance'      => $this->time_allowance,
                'country'             => $this->country,
                'iso3'                => $this->iso3,
                'region'              => $this->region,
                'status'              => $this->status,
                'response_code'       => $this->response_code,
                'response_message'    => $this->response_message,
                'created_at'          => $this->created_at,
                'updated_at'          => $this->updated_at,
            ]
        ];
    }

}
