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

    public function getAllApprovalFlow($requestInfo)
    {
        $orderBy = $requestInfo['orderBy'] ?? Utils::defaultOrderBy;
        $order = $requestInfo['order'] ?? Utils::defaultOrder;
        $perPage = $requestInfo['perPage'] ?? Utils::defaultPerPage;
        return $this->approvalFlow->orderBy($orderBy, $order)->paginate($perPage);
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

