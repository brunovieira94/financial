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

    public function getApprovalFlow($id)
    {
        return $this->approvalFlow->findOrFail($id);
    }

    public function postApprovalFlow($approvalFlowInfo)
    {
        $approvalFlow = new ApprovalFlow;
        return $approvalFlow->create($approvalFlowInfo);
    }

    public function putApprovalFlow($id, $approvalFlowInfo)
    {
        $approvalFlow = $this->approvalFlow->findOrFail($id);
        $approvalFlow->fill($approvalFlowInfo)->save();
        return $approvalFlow;
    }

    public function deleteApprovalFlow($id)
    {
        $this->approvalFlow->findOrFail($id)->delete();
        return true;
    }

}

