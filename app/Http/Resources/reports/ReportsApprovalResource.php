<?php

namespace App\Http\Resources\reports;

use Illuminate\Http\Resources\Json\JsonResource;

class ReportsApprovalResource extends JsonResource
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
            'order' => $this->order,
            'status' => $this->status,
            'reason' => $this->reason,
            'approver_stage' =>  $this->approver_stage,
            'approval_flow' => new ReportsApprovalFlowResource($this->approval_flow),
            'approver_stage_first' => $this->approver_stage_first,
        ];
    }
}
