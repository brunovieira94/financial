<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\BillingPaymentService as BillingPaymentService;
use App\Exports\BillingTransfeeraExport;

class BillingPaymentController extends Controller
{
    private $billingPaymentService;

    public function __construct(BillingPaymentService $billingPaymentService)
    {
        $this->billingPaymentService = $billingPaymentService;
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
}
