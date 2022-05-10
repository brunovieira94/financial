<?php

namespace App\Http\Controllers;

use App\Imports\CostCentersImport;
use App\Exports\CostCentersExport;
use Illuminate\Http\Request;
use App\Http\Requests\StoreCostCenterRequest;
use App\Http\Requests\PutCostCenterRequest;
use App\Services\CostCenterService as CostCenterService;

class CostCenterController extends Controller
{

    private $costCenterService;

    public function __construct(CostCenterService $costCenterService)
    {
        $this->costCenterService = $costCenterService;
    }

    public function index(Request $request)
    {
        return $this->costCenterService->getAllCostCenter($request->all());
    }

    public function costCenterFilterUser(Request $request)
    {
        return $this->costCenterService->costCenterFilterUser($request->all());
    }


    public function show($id)
    {
        return $this->costCenterService->getCostCenter($id);
    }

    public function store(StoreCostCenterRequest $request)
    {
        $costCenter = $this->costCenterService->postCostCenter($request->all());
        return response($costCenter, 201);
    }

    public function update(PutCostCenterRequest $request, $id)
    {
        return $this->costCenterService->putCostCenter($id, $request->all());
    }

    public function destroy($id)
    {
        $costCenter = $this->costCenterService->deleteCostCenter($id);
        return response('');
    }

    public function import()
    {
        (new CostCentersImport)->import(request()->file('import_file'));
        return response('');
    }


    public function export(Request $request)
    {
        if(array_key_exists('exportFormat', $request->all()))
        {
            if($request->all()['exportFormat'] == 'csv')
            {
                return (new CostCentersExport($request->all()))->download('centrosDeCusto.csv', \Maatwebsite\Excel\Excel::CSV, ['Content-Type' => 'text/csv']);
            }
        }
        return (new CostCentersExport($request->all()))->download('centrosDeCusto.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }

    public function allCostCenters(Request $request)
    {
        return $this->costCenterService->allCostCenters($request->all());
    }
}
