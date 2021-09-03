<?php

namespace App\Services;
use App\Models\CostCenter;

class CostCenterService
{
    private $costCenter;
    public function __construct(CostCenter $costCenter)
    {
        $this->costCenter = $costCenter;
    }

    public function getAllCostCenter()
    {
        return $this->costCenter->get();
    }

    public function getCostCenter($id)
    {
        return $this->costCenter->findOrFail($id);
    }

    public function postCostCenter($costCenterInfo)
    {
        $costCenter = new CostCenter;
        return $costCenter->create($costCenterInfo);
    }

    public function putCostCenter($id, $costCenterInfo)
    {
        $costCenter = $this->costCenter->findOrFail($id);
        $costCenter->fill($costCenterInfo)->save();
        return $costCenter;
    }

    public function deleteCostCenter($id)
    {
        $this->costCenter->findOrFail($id)->delete();
        return true;
    }

}

