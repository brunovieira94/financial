<?php

namespace App\Http\Controllers;

use App\Exports\PaidBillingInfoExport;
use Illuminate\Http\Request;
use App\Services\PaidBillingInfoService as PaidBillingInfoService;
//use App\Exports\PaidBillingInfoExport;
use App\Imports\PaidBillingInfoImport;
use App\Imports\DailyPaidBillingInfoImport;
use App\Models\PaidBillingInfo;
use Illuminate\Support\Facades\Artisan;
use App\Exports\Utils as UtilsExport;
use App\Jobs\NotifyUserOfCompletedExport;
use App\Models\Export;
use App\Services\Utils;

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
        ini_set('max_execution_time', 6000);
        Artisan::call('queue:work --stop-when-empty --timeout=6000', []);
        return response('');
    }

    public function truncate()
    {
        PaidBillingInfo::truncate();
        return response('');
    }

    public function getPaidBillingInfoClients()
    {
        return $this->paidBbillingService->getPaidBillingInfoClients();
    }

    // public function export(Request $request, $approvalStatus)
    // public function export(Request $request)
    // {
    //     ini_set('memory_limit', '1024M');
    //     $paidBillingInfo = PaidBillingInfo::query();
    //     $paidBillingInfo = Utils::baseFilterPaidBillingInfo($paidBillingInfo, $request->all());
    //     $count = $paidBillingInfo->count();
    //     $perPage = $count <= 20000 ? $count : 20000;
    //     $totalPages = $perPage == 0 ? 0 : intval(ceil($count/$perPage));

    //     for($i = 0; $i < $totalPages; $i++) {
    //         $fileName = $totalPages == 1 ? 'faturamentosPagos' : 'faturamentosPagosPt'.($i+1);
    //         $exportFile = UtilsExport::exportFile($request->all(), $fileName);
    //         (new PaidBillingInfoExport($request->all(), $perPage, ($i*$perPage), $exportFile['nameFile']))->store($exportFile['path'], 's3', $exportFile['extension'] == '.xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV)->chain([
    //             new NotifyUserOfCompletedExport($exportFile['path'], $exportFile['export']),
    //         ]);
    //     }

    //     return response()->json([
    //         'sucess' => $exportFile['export']->id
    //     ], 200);
    // }

    public function export(Request $request)
    {
        ini_set('memory_limit', '1024M');
        $paidBillingInfo = PaidBillingInfo::query();
        $paidBillingInfo = Utils::baseFilterPaidBillingInfo($paidBillingInfo, $request->all());
        $count = $paidBillingInfo->count();
        if($count > 25000) return response()->json([
            'error' => 'O arquivo gerado deve conter no máximo 25000 linhas'
        ], 422);
        $exportFile = UtilsExport::exportFile($request->all(), 'faturamentosPagos');
        UtilsExport::convertExportFormat($exportFile);
        $exportFileDB = Export::findOrFail($exportFile['id']);
        (new PaidBillingInfoExport($request->all(), $exportFile['nameFile']))
            ->queue($exportFileDB->path, 's3')
            ->allOnQueue('default')
            ->chain([
                new NotifyUserOfCompletedExport($exportFileDB->path, $exportFileDB),
            ]);
        // (new PaidBillingInfoExport($request->all(), $exportFile['nameFile']))->store($exportFile['path'], 's3', $exportFile['extension'] == '.xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV)->chain([
        //     new NotifyUserOfCompletedExport($exportFile['path'], $exportFile['export']),
        // ]);
        // return response()->json([
        //     'sucess' => $exportFile['export']->id
        // ], 200);
    }
}
