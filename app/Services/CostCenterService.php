<?php

namespace App\Services;
use App\Models\CostCenter;
use App\Models\ChartOfAccounts;

class CostCenterService
{
    private $costCenter;
    // private $chartOfAccounts;
    public function __construct(CostCenter $costCenter)
    {
        $this->costCenter = $costCenter;
        // $this->chartOfAccounts = $chartOfAccounts;
    }

    public function getAllCostCenter($requestInfo)
    {
        $costCenter = Utils::search($this->costCenter,$requestInfo);
        $costCenters = Utils::pagination($costCenter->where('parent', null),$requestInfo);
        //$costCenters = $this->costCenter->where('parent', null)->orderBy($orderBy, $order)->paginate($perPage);
        $nestable = $this->costCenter->nestable($costCenters);
        return $nestable;
    }

    public function getCostCenter($id)
    {
        $costCenter = $this->costCenter->findOrFail($id)->where('id', $id)->get();
        $nestable = $this->costCenter->nestable($costCenter);
        return $nestable;
    }

    public function postCostCenter($costCenterInfo)
    {
        $costCenter = new CostCenter;
        if(array_key_exists('parent', $costCenterInfo) && is_numeric($costCenterInfo['parent'])){
            $this->costCenter->findOrFail($costCenterInfo['parent'])->get();
        }
        return $costCenter->create($costCenterInfo);
    }

    public function putCostCenter($id, $costCenterInfo)
    {
        $costCenter = $this->costCenter->findOrFail($id);
        if(array_key_exists('parent', $costCenterInfo)){
            if(is_numeric($costCenterInfo['parent'])){
                $this->costCenter->findOrFail($costCenterInfo['parent'])->get();
            }
            if($costCenterInfo['parent'] == $id){
                abort(500);
            }
        }
        $costCenter->fill($costCenterInfo)->save();
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
}

