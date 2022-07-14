<?php

namespace App\Http\Controllers;

use App\Imports\ChartOfAccountsImport;
use App\Exports\ChartOfAccountsExport;
use Illuminate\Http\Request;
use App\Http\Requests\StoreChartOfAccountsRequest;
use App\Http\Requests\PutChartOfAccountsRequest;
use App\Models\ChartOfAccounts;
use App\Services\ChartOfAccountsService as ChartOfAccountsService;

class ChartOfAccountsController extends Controller
{

    private $chartOfAccountsService;

    public function __construct(ChartOfAccountsService $chartOfAccountsService)
    {
        $this->chartOfAccountsService = $chartOfAccountsService;
    }

    public function index(Request $request)
    {
        return $this->chartOfAccountsService->getAllChartOfAccounts($request->all());
    }

    public function show($id)
    {
        return $this->chartOfAccountsService->getChartOfAccounts($id);
    }

    public function store(StoreChartOfAccountsRequest $request)
    {
        $chartOfAccounts = $this->chartOfAccountsService->postChartOfAccounts($request->all());
        return response($chartOfAccounts, 201);
    }

    public function update(PutChartOfAccountsRequest $request, $id)
    {
        return $this->chartOfAccountsService->putChartOfAccounts($id, $request->all());
    }

    public function destroy($id)
    {
        if(ChartOfAccounts::where('parent', $id)->exists())
        {
            return response()->json([
                'error' => 'Este plano de contas está associado a outros planos de contas é necessário apagar e/ou alterar suas dependências antes de apagá lo.'
            ], 422);
        }
        $chartOfAccounts = $this->chartOfAccountsService->deleteChartOfAccounts($id);
        return response('');
    }

    public function import()
    {
        (new ChartOfAccountsImport)->import(request()->file('import_file'));
        return response('');
    }

    public function export(Request $request)
    {
        if(array_key_exists('exportFormat', $request->all()))
        {
            if($request->all()['exportFormat'] == 'csv')
            {
                return (new ChartOfAccountsExport($request->all()))->download('planoDeContas.csv', \Maatwebsite\Excel\Excel::CSV, ['Content-Type' => 'text/csv']);
            }
        }
        return (new ChartOfAccountsExport($request->all()))->download('planoDeContas.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }


    public function allChartOfAccounts(Request $request)
    {
        return $this->chartOfAccountsService->allChartOfAccounts($request->all());
    }

}
