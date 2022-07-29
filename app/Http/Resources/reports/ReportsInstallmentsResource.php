<?php

namespace App\Http\Resources\reports;

use Illuminate\Http\Resources\Json\JsonResource;

class ReportsInstallmentsResource extends JsonResource
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
            'portion_amount' => $this->portion_amount,
            'amount_received' => $this->amount_received,
            'fees' => $this->fees,
            'discount' => $this->discount,
            'initial_value' => $this->initial_value,
            'fine' => $this->fine,
            'percentage_discount' => $this->percentage_discount,
        ];
    }
}
