<?php

namespace App\Http\Resources\reports;

use Illuminate\Http\Resources\Json\JsonResource;

class RouteBillingLog extends JsonResource
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
        $reason = null;
        $concatenate = false;
        if ($this->motive != null) {
            $reason = $this->motive;
            $concatenate = true;
        }
        if ($this->description != null) {
            if ($concatenate) {
                $reason = $reason . ' - ' . $this->description;
            } else {
                $reason = $this->description;
            }
        }
        return [
            'type' => $this->type,
            'createdAt' => $this->created_at,
            'description' => $this->description,
            'causerUser' => $this->user_name,
            'causerUserRole' => $this->user_role,
            'createdUser' => $this->user_name,
            'motive' => $reason,
            'stage' => $this->stage
        ];
    }
}
