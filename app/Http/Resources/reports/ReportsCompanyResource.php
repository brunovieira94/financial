<?php

namespace App\Http\Resources\reports;

use Illuminate\Http\Resources\Json\JsonResource;

class ReportsCompanyResource extends JsonResource
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
            'trade_name' => $this->trade_name,
            'company_name' => $this->company_name,
        ];
    }
}
