<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\BillingService as BillingService;
use App\Services\CangoorooService as CangoorooService;
use App\Http\Requests\StoreBillingRequest;
use App\Http\Requests\PutBillingRequest;
use App\Exports\BillingExport;

class BillingController extends Controller
{
    private $billingService;
    private $cangoorooService;

    public function __construct(BillingService $billingService, CangoorooService $cangoorooService)
    {
        $this->billingService = $billingService;
        $this->cangoorooService = $cangoorooService;
    }

    public function index(Request $request, $approvalStatus)
    {
        return $this->billingService->getAllBilling($request->all(), $approvalStatus);
    }

    public function show($id)
    {
        return $this->billingService->getBilling($id);
    }

    public function store(StoreBillingRequest $request)
    {
        return $this->billingService->postBilling($request->all());
    }

    public function update(PutBillingRequest $request, $id)
    {
        return $this->billingService->putBilling($id, $request->all());
    }

    public function approve($id)
    {
        return $this->billingService->approve($id);
    }

    public function reprove($id, Request $request)
    {
        return $this->billingService->reprove($id, $request);
    }

    public function destroy($id)
    {
        $this->billingService->deleteBilling($id);
        return response('');
    }

    public function getCangoorooData(Request $request)
    {
        try {
            $cangooroo = $this->cangoorooService->updateCangoorooData($request->booking_id, $request->reserve);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
        if (is_array($cangooroo) && array_key_exists('error', $cangooroo)) {
            return response()->json([
                'error' => $cangooroo['error'],
            ], 422);
        }
        return $cangooroo;
    }

    public function export(Request $request, $approvalStatus)
    {
        if (array_key_exists('exportFormat', $request->all()) && $request->all()['exportFormat'] == 'csv') {
            return (new BillingExport($request->all(), $approvalStatus))->download('faturamento.csv', \Maatwebsite\Excel\Excel::CSV, ['Content-Type' => 'text/csv']);
        }
        return (new BillingExport($request->all(), $approvalStatus))->download('faturamento.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }
}
