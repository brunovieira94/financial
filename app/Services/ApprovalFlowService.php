<?php

namespace App\Services;
use App\Models\ApprovalFlow;

class ApprovalFlowService
{
    private $approvalFlow;
    public function __construct(ApprovalFlow $approvalFlow)
    {
        $this->approvalFlow = $approvalFlow;
    }

    public function getAllApprovalFlow()
    {
        return $this->approvalFlow->get();
    }

    public function postApprovalFlow($approvalFlowInfo)
    {
        $approvalFlow = new ApprovalFlow;
        $info = [];
        foreach($approvalFlowInfo['order'] as $key=>$roles){
            $info['order'] = $key;
            foreach($roles as $role){
                $info['role_id'] = $role;
                $approvalFlow->create($info);
            }
        }
        return true;
    }

}

