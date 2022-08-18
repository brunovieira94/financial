<?php

namespace App\Http\Resources\reports;

use Illuminate\Http\Resources\Json\JsonResource;

class ReportsCnabGeneratedResource extends JsonResource
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
            'file_date' => $this->file_date,
        ];
    }
}
