<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreMeasurementUnitRequest;
use App\Services\MeasurementUnitService as MeasurementUnitService;

class MeasurementUnitController extends Controller
{

    private $measurementUnitService;

    public function __construct(MeasurementUnitService $measurementUnitService)
    {
        $this->measurementUnitService = $measurementUnitService;
    }

    public function index(Request $request)
    {
        return $this->measurementUnitService->getAllMeasurementUnit($request->all());
    }

    public function show($id)
    {
        return $this->measurementUnitService->getMeasurementUnit($id);
    }

    public function store(StoreMeasurementUnitRequest $request)
    {
        return $this->measurementUnitService->postMeasurementUnit($request->all());
    }

    public function update(StoreMeasurementUnitRequest $request, $id)
    {
        return $this->measurementUnitService->putMeasurementUnit($id, $request->all());
    }

    public function destroy($id)
    {
        $measurementUnit = $this->measurementUnitService->deleteMeasurementUnit($id);
        return response('');
    }

}
