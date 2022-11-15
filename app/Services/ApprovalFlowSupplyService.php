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
        foreach ($approvalFlowSupplyInfo['order'] as $key => $roles) {
            $info['order'] = $key;
            foreach ($roles as $role) {
                $info['role_id'] = $role;
                $approvalFlowSupply->create($info);
            }
        }
        return true;
    }

    public function getAllApprovalFlowSupplyUsers($requestInfo)
    {
        if (array_key_exists('search', $requestInfo)) {
            if (array_key_exists('searchFields', $requestInfo)) {
                $users = ApprovalFlowSupply::join('users', 'approval_flow_supply.role_id', '=', 'users.role_id')
                    ->whereLike($requestInfo['searchFields'], "%{$requestInfo['search']}%")
                    ->where('users.deleted_at', null)
                    ->get(['users.id', 'users.name']);
            } else {
                $users = ApprovalFlowSupply::join('users', 'approval_flow_supply.role_id', '=', 'users.role_id')
                    ->where('users.deleted_at', null)
                    ->get(['users.id', 'users.name']);
            }
        } else {
            $users = ApprovalFlowSupply::join('users', 'approval_flow_supply.role_id', '=', 'users.role_id')
                ->where('users.deleted_at', null)
                ->get(['users.id', 'users.name']);
        }

        $data = [
            "data" => $users->unique()
        ];
        return $data;
    }
}
