<?php

namespace App\Http\Resources\reports;

use Illuminate\Http\Resources\Json\JsonResource;

class ReportsFirstApprovalPaymentRequestResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'user_name' => $this['user_name'],
            'user_role' => $this['user_role'],
            'created_at' => $this['created_at'],
        ];
    }
}
