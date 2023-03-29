<?php

namespace App\Http\Resources\reports;

use Illuminate\Http\Resources\Json\JsonResource;

class RouteApprovalFlowByUserResource extends JsonResource
{
    public static $wrap = null;
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
            'installments' => ReportsInstallmentsResource::collection($this->installments),
            'company' => new ReportsCompanyResource($this->company),
            'next_extension_date' => $this->next_extension_date,
            'created_at' => $this->created_at,
            'cost_center' => new ReportsCostCenterResource($this->cost_center),
            'approval' => new ReportsApprovalResource($this->approval),
            'applicant_can_edit' => $this->applicant_can_edit,
            'next_extension_date' => $this->next_extension_date,
            'payment_type' => $this->payment_type,
            'emission_date' => $this->emission_date,
            'net_value' => $this->net_value,
            'invoice_number' => $this->invoice_number,
            'pay_date' => $this->pay_date,
            'currency' => new ReportsCurrencyResource($this->currency),
            'first_approval_financial_analyst' => new ReportsFirstApprovalPaymentRequestResource($this->first_approval_financial_analyst),
            'stage_for_disapproval' => ReportsStageForDisapproval::collection($this->stage_for_disapproval),
            'installment_link' => $this->installment_link,
            'advance' => $this->advance,
            'allow_binding' => $this->allow_binding,
        ];
    }
}
