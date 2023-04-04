<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\BillingPaymentService as BillingPaymentService;
use App\Exports\BillingTransfeeraExport;
use App\Exports\BillingPaymentExport;
use App\Imports\SetPayBillingImport;
use App\Exports\Utils as UtilsExport;
use App\Jobs\NotifyUserOfCompletedExport;
use App\Models\Export;

class BillingPaymentController extends Controller
{
    private $billingPaymentService;
    private $setPayBillingImport;

    public function __construct(BillingPaymentService $billingPaymentService, SetPayBillingImport $setPayBillingImport)
    {
        $this->billingPaymentService = $billingPaymentService;
        $this->setPayBillingImport = $setPayBillingImport;
    }

    public function index(Request $request)
    {
        return $this->billingPaymentService->getAllBillingPayment($request->all());
    }

    public function show($id)
    {
        return $this->billingPaymentService->getBillingPayment($id);
    }

    public function destroy($id)
    {
        return $this->billingPaymentService->deleteBillingPayment($id);
    }

    public function transfeeraExport(Request $request)
    {
        if (array_key_exists('exportFormat', $request->all()) && $request->all()['exportFormat'] == 'csv') {
            return (new BillingTransfeeraExport($request->all()))->download('transfeera.csv', \Maatwebsite\Excel\Excel::CSV, ['Content-Type' => 'text/csv']);
        }
        return (new BillingTransfeeraExport($request->all()))->download('transfeera.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }

    public function export(Request $request)
    {
        $exportFile = UtilsExport::exportFile($request->all(), 'pagamentos');

        (new BillingPaymentExport($request->all(), $exportFile['nameFile']))->store($exportFile['path'], 's3', $exportFile['extension'] == '.xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV)->chain([
            new NotifyUserOfCompletedExport($exportFile['path'], $exportFile['export']),
        ]);

        return response()->json([
            'sucess' => $exportFile['export']->id
        ], 200);
    }

    public function import()
    {
        $this->setPayBillingImport->import(request()->file('import_file'));
        return response([
            'not_imported' => $this->setPayBillingImport->not_imported,
            'imported' => $this->setPayBillingImport->imported,
        ]);
    }
}
