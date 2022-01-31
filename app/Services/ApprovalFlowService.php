<?php

namespace App\Services;
use App\Models\ApprovalFlow;

class ApprovalFlowService
{
    private $with = ['role'];
    private $approvalFlow;
    public function __construct(ApprovalFlow $approvalFlow)
    {
        $this->approvalFlow = $approvalFlow;
    }

    public function getAllApprovalFlow()
    {
        return $this->approvalFlow->with($this->with)->get();
    }

    public function postApprovalFlow($approvalFlowInfo)
    {
        ApprovalFlow::truncate();
        $approvalFlow = new ApprovalFlow;
        $info = [];
        foreach($approvalFlowInfo['order'] as $key=>$roles){
            $info['order'] = $key;
            $info['competency'] = $approvalFlowInfo['competency'][$key];
            $info['extension'] = $approvalFlowInfo['extension'][$key];
            $info['filter_cost_center'] = $approvalFlowInfo['filter_cost_center'][$key];
            foreach($roles as $role){
                $info['role_id'] = $role;
                $approvalFlow->create($info);
            }
        }
        return true;
    }

}

