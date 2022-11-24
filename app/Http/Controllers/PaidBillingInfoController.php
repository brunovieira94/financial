<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PaidBillingInfoService as PaidBillingInfoService;
//use App\Exports\PaidBillingInfoExport;
use App\Imports\PaidBillingInfoImport;
use App\Imports\DailyPaidBillingInfoImport;
use Illuminate\Support\Facades\Artisan;

class PaidBillingInfoController extends Controller
{
    private $paidBbillingService;
    private $paidBillingInfoImport;
    private $dailyPaidBillingInfoImport;

    public function __construct(PaidBillingInfoService $paidBbillingService, PaidBillingInfoImport $paidBillingInfoImport, DailyPaidBillingInfoImport $dailyPaidBillingInfoImport)
    {
        $this->paidBbillingService = $paidBbillingService;
        $this->paidBillingInfoImport = $paidBillingInfoImport;
        $this->dailyPaidBillingInfoImport = $dailyPaidBillingInfoImport;
    }

    public function index(Request $request)
    {
        return $this->paidBbillingService->getAllPaidBillingInfo($request->all());
    }

    public function show($id)
    {
        return $this->paidBbillingService->getPaidBillingInfo($id);
    }

    public function destroy($id)
    {
        $this->paidBbillingService->deletePaidBillingInfo($id);
        return response('');
    }

    public function import()
    {
        ini_set('max_execution_time', 300);
        $this->paidBillingInfoImport->import(request()->file('import_file'));
        return response('');
    }

    public function dailyImport()
    {
        $this->dailyPaidBillingInfoImport->import(request()->file('import_file'));
        return response([
            'not_imported' => $this->dailyPaidBillingInfoImport->not_imported,
            'imported' => $this->dailyPaidBillingInfoImport->imported,
        ]);
    }

    public function work()
    {
        ini_set('max_execution_time', 300);
        Artisan::call('queue:work --stop-when-empty', []);
        return response('');
    }

    // public function export(Request $request, $approvalStatus)
    // {
    //     if (array_key_exists('exportFormat', $request->all()) && $request->all()['exportFormat'] == 'csv') {
    //         return (new PaidBillingInfoExport($request->all(), $approvalStatus))->download('faturamentosPagos.csv', \Maatwebsite\Excel\Excel::CSV, ['Content-Type' => 'text/csv']);
    //     }
    //     return (new PaidBillingInfoExport($request->all(), $approvalStatus))->download('faturamentosPagos.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    // }
}
