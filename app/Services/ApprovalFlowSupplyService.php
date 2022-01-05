<?php

namespace App\Services;
use App\Models\ApprovalFlowSupply;

class ApprovalFlowSupplyService
{
    private $with = ['role'];
    private $approvalFlowSupply;
    public function __construct(ApprovalFlowSupply $approvalFlowSupply)
    {
        $this->approvalFlowSupply = $approvalFlowSupply;
    }

    public function getAllApprovalFlowSupply()
    {
        return $this->approvalFlowSupply->with($this->with)->get();
    }

    public function postApprovalFlowSupply($approvalFlowSupplyInfo)
    {
        ApprovalFlowSupply::truncate();
        $approvalFlowSupply = new ApprovalFlowSupply;
        $info = [];
        foreach($approvalFlowSupplyInfo['order'] as $key=>$roles){
            $info['order'] = $key;
            foreach($roles as $role){
                $info['role_id'] = $role;
                $approvalFlowSupply->create($info);
            }
        }
        return true;
    }

}

