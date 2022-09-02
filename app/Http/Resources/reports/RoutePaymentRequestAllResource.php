<?php

namespace App\Http\Resources\reports;

use Illuminate\Http\Resources\Json\JsonResource;

class RoutePaymentRequestAllResource extends JsonResource
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
        ];
    }
}
