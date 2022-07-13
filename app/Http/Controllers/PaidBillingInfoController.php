<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PaidBillingInfoService as PaidBillingInfoService;
//use App\Exports\PaidBillingInfoExport;
use App\Imports\PaidBillingInfoImport;
use App\Imports\DailyPaidBillingInfoImport;

class PaidBillingInfoController extends Controller
{
    private $billingService;
    private $paidBillingInfoImport;
    private $dailyPaidBillingInfoImport;

    public function __construct(PaidBillingInfoService $billingService, PaidBillingInfoImport $paidBillingInfoImport, DailyPaidBillingInfoImport $dailyPaidBillingInfoImport)
    {
        $this->billingService = $billingService;
        $this->paidBillingInfoImport = $paidBillingInfoImport;
        $this->dailyPaidBillingInfoImport = $dailyPaidBillingInfoImport;
    }

    public function index(Request $request, $approvalStatus)
    {
        return $this->billingService->getAllPaidBillingInfo($request->all(), $approvalStatus);
    }

    public function show($id)
    {
        return $this->billingService->getPaidBillingInfo($id);
    }

    public function destroy($id)
    {
        $this->billingService->deletePaidBillingInfo($id);
        return response('');
    }

    public function import()
    {
        $this->paidBillingInfoImport->import(request()->file('import_file'));
        return response('');
        return response([
            'not_imported' => $this->paidBillingInfoImport->not_imported,
            'imported' => $this->paidBillingInfoImport->imported,
        ]);
    }

    public function dailyImport()
    {
        $this->dailyPaidBillingInfoImport->import(request()->file('import_file'));
        return response([
            'not_imported' => $this->dailyPaidBillingInfoImport->not_imported,
            'imported' => $this->dailyPaidBillingInfoImport->imported,
        ]);
    }

    // public function export(Request $request, $approvalStatus)
    // {
    //     if (array_key_exists('exportFormat', $request->all()) && $request->all()['exportFormat'] == 'csv') {
    //         return (new PaidBillingInfoExport($request->all(), $approvalStatus))->download('faturamentosPagos.csv', \Maatwebsite\Excel\Excel::CSV, ['Content-Type' => 'text/csv']);
    //     }
    //     return (new PaidBillingInfoExport($request->all(), $approvalStatus))->download('faturamentosPagos.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    // }
}
