<?php

namespace App\Http\Resources\reports;

use Illuminate\Http\Resources\Json\JsonResource;

class BillingPaymentBillingsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'reserve' => $this->reserve,
            'cangooroo_service_id' => $this->cangooroo_service_id,
            'supplier_value' => $this->supplier_value,
            'payment_status' => $this->payment_status,
            'status_123' => $this->status_123,
            'cangooroo' => new CangoorooResource($this->cangooroo),
        ];
    }
}
