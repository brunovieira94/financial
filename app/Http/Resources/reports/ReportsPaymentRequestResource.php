<?php

namespace App\Http\Resources\reports;

use Illuminate\Http\Resources\Json\JsonResource;

class ReportsPaymentRequestResource extends JsonResource
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
            'emission_date' => $this->emission_date,
            'pay_date' => $this->pay_date,
            'amount' => $this->amount,
            'net_value' =>  $this->net_value,
            'provider' => new ReportsProviderResource($this->provider),
            'currency' => new ReportsCurrencyResource($this->currency),
        ];
    }
}
