<?php

namespace App\Http\Controllers;

use App\Imports\CostCentersImport;
use App\Exports\CostCentersExport;
use App\Exports\CostCentersExportEditable;
use Illuminate\Http\Request;
use App\Http\Requests\StoreCostCenterRequest;
use App\Http\Requests\PutCostCenterRequest;
use App\Imports\CostCentersImportEditable;
use App\Models\CostCenter;
use App\Models\PaymentRequest;
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
        if (CostCenter::where('parent', $id)->exists()) {
            return response()->json([
                'error' => 'Este centro de custo está associado a outros centro de custos é necessário apagar e/ou alterar suas dependências antes de apagá lo.'
            ], 422);
        }

        if (PaymentRequest::where('cost_center_id', $id)->exists()) {
            return response()->json([
                'error' => 'Este centro de custo está associado a uma ou várias solicitações de pagamento.'
            ], 422);
        }

        $costCenter = $this->costCenterService->deleteCostCenter($id);
        return response('');
    }

    public function import()
    {
        try {
            (new CostCentersImportEditable)->import(request()->file('import_file'));
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $line = $failures[0]->row() ?? '';
            $error = $failures[0]->errors()[0] ?? '';
            $errorArray = explode('.', $error);

            if ($line == 0) {
                $line  = (int) filter_var($error, FILTER_SANITIZE_NUMBER_INT);
                $error = $errorArray[1];
            } else {
                $error = $errorArray[0] . '. ' . $errorArray[1];
            }
            $error = 'erro encontrado na linha ' . $line . '. ' . $error;

            return Response()->json([
                'error' => $error
            ], 422);
        }
        return response('');
    }


    public function export(Request $request)
    {
        if (array_key_exists('exportFormat', $request->all())) {
            if ($request->all()['exportFormat'] == 'csv') {
                return (new CostCentersExportEditable($request->all()))->download('centrosDeCusto.csv', \Maatwebsite\Excel\Excel::CSV, ['Content-Type' => 'text/csv']);
            }
        }
        return (new CostCentersExportEditable($request->all()))->download('centrosDeCusto.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }

    public function allCostCenters(Request $request)
    {
        return $this->costCenterService->allCostCenters($request->all());
    }
}
