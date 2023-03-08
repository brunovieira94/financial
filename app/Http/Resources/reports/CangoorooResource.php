<?php

namespace App\Http\Resources\reports;

use Illuminate\Http\Resources\Json\JsonResource;

class CangoorooResource extends JsonResource
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
            'service_id' => $this->service_id,
            'hotel_id' => $this->hotel_id,
            'status' => $this->status,
            'hotel' => new HotelResource($this->hotel),
        ];
    }
}
