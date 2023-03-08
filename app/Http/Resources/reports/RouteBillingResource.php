<?php

namespace App\Http\Resources\reports;

use Illuminate\Http\Resources\Json\JsonResource;

class RouteBillingResource extends JsonResource
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
            'pax_in_house' => $this->pax_in_house,
            'suggestion' => $this->suggestion,
            'suggestion_reason' => $this->suggestion_reason,
            'reserve' => $this->reserve,
            'cangooroo_service_id' => $this->cangooroo_service_id,
            'form_of_payment' => $this->form_of_payment,
            'supplier_value' => $this->supplier_value,
            'pay_date' => $this->pay_date,
            'oracle_protocol' => $this->oracle_protocol,
            'cnpj' => $this->cnpj,
            'approval_status' => $this->approval_status,
            'updated_at' => $this->updated_at,
            'approval_flow' => new HotelApprovalFlowResource($this->approval_flow),
            'cangooroo' => new CangoorooResource($this->cangooroo),
            'billing_payment' => new BillingPaymentResource($this->billing_payment),
        ];
    }
}
