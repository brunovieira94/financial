<?php

namespace App\Services;

use App\Models\ApprovalFlow;
use App\Models\GroupApprovalFlow;
use Illuminate\Support\Facades\DB;

class ApprovalFlowService
{
    private $with = ['approval_flow'];
    private $approvalFlow;
    private $groupApprovalFlow;

    public function __construct(ApprovalFlow $approvalFlow, GroupApprovalFlow $groupApprovalFlow)
    {
        $this->approvalFlow = $approvalFlow;
        $this->groupApprovalFlow = $groupApprovalFlow;
    }

    public function getAllApprovalFlow($requestInfo)
    {
        $approvalFlow = Utils::search($this->groupApprovalFlow,$requestInfo);
        return Utils::pagination($approvalFlow, $requestInfo);
    }

    public function getApprovalFlowById($id)
    {
        return $this->groupApprovalFlow->with($this->with)->get();
    }


    public function postApprovalFlow($approvalFlowInfo)
    {
        $groupApprovalFlow = $this->groupApprovalFlow->create($approvalFlowInfo);
        $approvalFlow = new ApprovalFlow;
        $info = [];
        foreach ($approvalFlowInfo['order'] as $key => $roles) {
            $info['order'] = $key;
            $info['competency'] = $approvalFlowInfo['competency'][$key];
            $info['extension'] = $approvalFlowInfo['extension'][$key];
            $info['filter_cost_center'] = $approvalFlowInfo['filter_cost_center'][$key];
            $info['group_approval_flow_id'] = $groupApprovalFlow->id;
            foreach ($roles as $role) {
                $info['role_id'] = $role;
                $approvalFlow->create($info);
            }
        }
        return true;
    }

    public function putApprovalFlow($id, $approvalFlowInfo)
    {
        $groupApprovalFlow = $this->groupApprovalFlow->findOrFail($id);
        DB::table('approval_flow')->where('group_approval_flow_id', $id)->delete();

        $approvalFlow = new ApprovalFlow;
        $info = [];
        foreach ($approvalFlowInfo['order'] as $key => $roles) {
            $info['order'] = $key;
            $info['competency'] = $approvalFlowInfo['competency'][$key];
            $info['extension'] = $approvalFlowInfo['extension'][$key];
            $info['filter_cost_center'] = $approvalFlowInfo['filter_cost_center'][$key];
            $info['group_approval_flow_id'] = $groupApprovalFlow->id;
            foreach ($roles as $role) {
                $info['role_id'] = $role;
                $approvalFlow->create($info);
            }
        }
        return true;
    }

    public function deleteApprovalFlow($id)
    {
        $this->groupApprovalFlow->findOrFail($id)->delete();
        DB::table('approval_flow')->where('group_approval_flow_id', $id)->delete();
        return true;
    }
}
