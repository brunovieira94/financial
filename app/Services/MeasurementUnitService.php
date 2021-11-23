<?php

namespace App\Services;
use App\Models\MeasurementUnit;

class MeasurementUnitService
{
    private $measurementUnit;
    public function __construct(MeasurementUnit $measurementUnit)
    {
        $this->measurementUnit = $measurementUnit;
    }

    public function getAllMeasurementUnit($requestInfo)
    {
        $measurementUnit = Utils::search($this->measurementUnit,$requestInfo);
        return Utils::pagination($measurementUnit,$requestInfo);
    }

    public function getMeasurementUnit($id)
    {
      return $this->measurementUnit->findOrFail($id);
    }

    public function postMeasurementUnit($measurementUnitInfo)
    {
        $measurementUnit = new MeasurementUnit;
        return $measurementUnit->create($measurementUnitInfo);
    }

    public function putMeasurementUnit($id, $measurementUnitInfo)
    {
        $measurementUnit = $this->measurementUnit->findOrFail($id);
        $measurementUnit->fill($measurementUnitInfo)->save();
        return $measurementUnit;
    }

    public function deleteMeasurementUnit($id)
    {
      $this->measurementUnit->findOrFail($id)->delete();
      return true;
    }

}
