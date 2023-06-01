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

    public function export(Request $request)
    {
        // $infoRequest = $request->all();
        // $paidBillingInfo = PaidBillingInfo::query();
        // if (array_key_exists('created_at', $infoRequest)) {
        //     if (array_key_exists('from', $infoRequest['created_at'])) {
        //         $paidBillingInfo->where('created_at', '>=', $infoRequest['created_at']['from']);
        //     }
        //     if (array_key_exists('to', $infoRequest['created_at'])) {
        //         $paidBillingInfo->where('created_at', '<=', date("Y-m-d", strtotime("+1 days", strtotime($infoRequest['created_at']['to']))));
        //     }
        //     if (!array_key_exists('to', $infoRequest['created_at']) && !array_key_exists('from', $infoRequest['created_at'])) {
        //         $paidBillingInfo->whereBetween('created_at', [now()->addMonths(-1), now()]);
        //     }
        // }
        // if (array_key_exists('pay_date', $infoRequest)) {
        //     if (array_key_exists('from', $infoRequest['pay_date'])) {
        //         $paidBillingInfo->where('pay_date', '>=', $infoRequest['pay_date']['from']);
        //     }
        //     if (array_key_exists('to', $infoRequest['pay_date'])) {
        //         $paidBillingInfo->where('pay_date', '<=', $infoRequest['pay_date']['to']);
        //     }
        //     if (!array_key_exists('to', $infoRequest['pay_date']) && !array_key_exists('from', $infoRequest['pay_date'])) {
        //         $paidBillingInfo->whereBetween('pay_date', [now(), now()->addMonths(1)]);
        //     }
        // }
        // if (array_key_exists('form_of_payment', $infoRequest)) {
        //     $paidBillingInfo->where('form_of_payment', $infoRequest['form_of_payment']);
        // }
        // if (array_key_exists('cnpj', $infoRequest)) {
        //     $paidBillingInfo->where('cnpj_hotel', $infoRequest['cnpj']);
        // }
        // if (array_key_exists('service_id', $infoRequest)) {
        //     $paidBillingInfo->where('service_id', $infoRequest['service_id']);
        // }
        // if (array_key_exists('reserve', $infoRequest)) {
        //     $paidBillingInfo->where('reserve', $infoRequest['reserve']);
        // }
        // $count = $paidBillingInfo->count();
        // $perPage = 20000;
        // $totalPages = intval(ceil($count/$perPage));

        // for($i = 0; $i < $totalPages; $i++) {
        //     $fileName = $totalPages == 1 ? 'faturamentosPagos' : 'faturamentosPagos - Parte '.($i+1);
        //     $exportFile = UtilsExport::exportFile($request->all(), $fileName);
        //     $paginated = $paidBillingInfo->paginate($perPage, ['*'], 'page', ($i+1));
        //     (new PaidBillingInfoExport($paginated, $exportFile['nameFile']))->store($exportFile['path'], 's3', $exportFile['extension'] == '.xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV)->chain([
        //         new NotifyUserOfCompletedExport($exportFile['path'], $exportFile['export']),
        //     ]);
        // }

            $exportFile = UtilsExport::exportFile($request->all(), 'faturamentosPagos');
            (new PaidBillingInfoExport($request->all(), $exportFile['nameFile']))->store($exportFile['path'], 's3', $exportFile['extension'] == '.xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV)->chain([
                new NotifyUserOfCompletedExport($exportFile['path'], $exportFile['export']),
            ]);

        return response()->json([
            'sucess' => $exportFile['export']->id
        ], 200);
    }
}
