<?php

namespace App\Http\Resources\reports;

use Illuminate\Http\Resources\Json\JsonResource;

class RouteApprovalUserResource extends JsonResource
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
            'id' => $this->payment_request_id,
            'type' => $this->type,
            'motive' => $this->motive,
            'stage' => $this->stage,
            'description' => $this->description,
            'user_name' => $this->user_name,
            'user_role' => $this->user_role,
            'recipient' => $this->recipient,
            'created_at' => $this->created_at,
            'user' => new ReportsUserResource($this->user),
            'payment_request' => new ReportsPaymentRequestResource($this->payment_request),
        ];
    }
}
