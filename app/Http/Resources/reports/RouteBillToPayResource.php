<?php

namespace App\Http\Resources\reports;

use Illuminate\Http\Resources\Json\JsonResource;

class RouteBillToPayResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'provider' => new ReportsProviderResource($this->provider),
            'next_extension_date' => $this->next_extension_date,
            'next_competence_date' => $this->next_competence_date,
            'payment_type' => $this->payment_type,
            'emission_date' => $this->emission_date,
            //'company' => new ReportsCompanyResource($this->company),
            'created_at' => $this->created_at,
            'cost_center' => new ReportsCostCenterResource($this->cost_center),
            'approval' => new ReportsApprovalResource($this->approval),
            'days_late' => $this->days_late,
            'amount' => $this->amount,
            'cnab_payment_request' => new ReportsCnabPaymentRequestResource($this->cnab_payment_request),
        ];
    }
}
