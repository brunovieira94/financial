<?php

namespace App\Services;

use App\Models\CostCenter;
use App\Models\CostCenterHasManager;
use App\Models\CostCenterHasVicePresident;
use App\Models\GroupApprovalFlow;

use function PHPSTORM_META\map;
use function PHPUnit\Framework\isNull;

class CostCenterService
{
    private $costCenter;
    private $with = ['group_approval_flow.approval_flow'];
    // private $chartOfAccounts;
    public function __construct(CostCenter $costCenter)
    {
        $this->costCenter = $costCenter;
        // $this->chartOfAccounts = $chartOfAccounts;
    }

    public function getAllCostCenter($requestInfo)
    {
        $costCenter = Utils::search($this->costCenter, $requestInfo);
        if (array_key_exists('display_assets', $requestInfo)) {
            if ($requestInfo['display_assets']) {
                $costCenter = $costCenter->where('active', true);
            }
        }
        $costCenters = Utils::pagination($costCenter->where('parent', null), $requestInfo);
        //$costCenters = $this->costCenter->where('parent', null)->orderBy($orderBy, $order)->paginate($perPage);
        $nestable = $this->costCenter->nestable($costCenters);
        foreach ($nestable as $nest) {
            $nest->group_approval_flow = GroupApprovalFlow::where('id', $nest->group_approval_flow_id)->first();
        }
        return $nestable;
    }

    public function getCostCenter($id)
    {
        $costCenter = $this->costCenter->with($this->with)->findOrFail($id)->where('id', $id)->get();
        $nestable = $this->costCenter->nestable($costCenter);
        $costCenter = $this->costCenter->with($this->with)->findOrFail($id);
        foreach ($nestable as $nest) {
            $nest->group_approval_flow = GroupApprovalFlow::where('id', $costCenter->group_approval_flow_id)->with('approval_flow')->first();
        }
        return $nestable;
    }

    public function costCenterFilterUser($requestInfo)
    {
        if (auth()->user()->role->filter_cost_center) {

            $costCenterID = auth()->user()->cost_center->pluck('id');

            $costCenter = Utils::search($this->costCenter, $requestInfo);
            if (array_key_exists('display_assets', $requestInfo)) {
                if ($requestInfo['display_assets']) {
                    $costCenter = $costCenter->where('active', true);
                }
            }
            $costCenters = Utils::pagination(
                $costCenter
                    ->where('parent', null)
                    ->whereIn('id', $costCenterID),
                $requestInfo
            );
            $nestable = $this->costCenter->nestable($costCenters);
            foreach ($nestable as $nest) {
                $nest->group_approval_flow = GroupApprovalFlow::where('id', $nest->group_approval_flow_id)->first();
            }
            return $nestable;
        } else {
            return self::getAllCostCenter($requestInfo);
        }
    }


    public function postCostCenter($costCenterInfo)
    {
        $costCenter = new CostCenter;
        if (array_key_exists('parent', $costCenterInfo) && is_numeric($costCenterInfo['parent'])) {
            $this->costCenter->findOrFail($costCenterInfo['parent'])->get();
        }
        $costCenter = $costCenter->create($costCenterInfo);
        $this->synVicePresidentsManagers($costCenter->id, $costCenterInfo);
        return $this->costCenter->with($this->with)->findOrFail($costCenter->id);
    }

    public function putCostCenter($id, $costCenterInfo)
    {
        $costCenter = $this->costCenter->findOrFail($id);
        if (array_key_exists('parent', $costCenterInfo)) {
            if (is_numeric($costCenterInfo['parent'])) {
                $this->costCenter->findOrFail($costCenterInfo['parent'])->get();
            }
            if ($costCenterInfo['parent'] == $id) {
                abort(500);
            }
        }
        $costCenter->fill($costCenterInfo)->save();
        $this->synVicePresidentsManagers($id, $costCenterInfo);
        return $costCenter;
    }

    public function deleteCostCenter($id)
    {
        $costCenter = $this->costCenter->findOrFail($id)->where('id', $id)->get();
        // $collection = $this->chartOfAccounts->where('cost_center_id', $id)->get(['id']);
        $nestable = $this->costCenter->nestable($costCenter)->toArray();
        $arrayIds = Utils::getDeleteKeys($nestable);
        $this->costCenter->destroy($arrayIds);
        // $this->chartOfAccounts->destroy($collection->toArray());
        return true;
    }

    public function allCostCenters($costCenterInfo)
    {
        $costCenters = Utils::search($this->costCenter, $costCenterInfo);
        if (array_key_exists('display_assets', $costCenterInfo)) {
            if ($costCenterInfo['display_assets']) {
                $costCenters = $costCenters->where('active', true);
            }
        }
        $costCenters = Utils::pagination($costCenters->with($this->with), $costCenterInfo);

        //foreach ($costCenters as $costCenter)
        //{
        //    if($costCenter->parent != NULL)
        //    {
        //        $costCenter->code = CostCenter::findOrFail($costCenter->parent)->code .'.' . $costCenter->code;
        //    }
        //}
        return $costCenters;
    }

    public function synVicePresidentsManagers($id, $requestInfo)
    {
        if (array_key_exists('vice_presidents', $requestInfo)) {
            CostCenterHasVicePresident::where('cost_center_id', $id)->delete();
            foreach ($requestInfo['vice_presidents'] as $idUser) {
                CostCenterHasVicePresident::create([
                    'cost_center_id' => $id,
                    'vice_president_user_id' => $idUser,
                ]);
            }
        }

        if (array_key_exists('managers', $requestInfo)) {
            CostCenterHasManager::where('cost_center_id', $id)->delete();
            foreach ($requestInfo['managers'] as $idUser) {
                CostCenterHasManager::create([
                    'cost_center_id' => $id,
                    'manager_user_id' => $idUser,
                ]);
            }
        }
    }
}
