<?php

namespace App\Http\Resources\reports;

use Illuminate\Http\Resources\Json\JsonResource;

class ReportsInstallmentBankProviderResource extends JsonResource
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
            'id' =>  $this->id,
            'agency_number' =>  $this->agency_number,
            'agency_check_number' =>  $this->account_number,
            'account_check_number' =>  $this->account_check_number,
            'account_number' =>  $this->account_number,
            'pix_key' =>  $this->pix_key,
            'pix_key_type' =>  $this->pix_key_type,
            'account_type' =>  $this->account_type,
            'entity_type' =>  $this->entity_type,
            'entity_name' =>  $this->entity_name,
            'cpf_cnpj' =>  $this->cpf_cnpj,
            'international' =>  $this->international,
            'address' =>  $this->address,
            'covenant'  =>  $this->covenant,
            'iban_code'  =>  $this->iban_code,
            'bank' => new ReportsBankNameResource($this->bank),
        ];
    }
}
