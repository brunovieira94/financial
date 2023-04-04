<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\BillingService as BillingService;
use App\Services\CangoorooService as CangoorooService;
use App\Http\Requests\StoreBillingRequest;
use App\Http\Requests\PutBillingRequest;
use App\Exports\BillingExport;
use App\Exports\BillingForApprovalExport;
use App\Exports\Utils as UtilsExport;
use App\Jobs\NotifyUserOfCompletedExport;
use App\Models\Export;

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

    public function getBillingsForApproval(Request $request)
    {
        return $this->billingService->getAllBillingsForApproval($request->all());
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

    public function approveMany(Request $request)
    {
        return $this->billingService->approveMany($request->all());
    }

    public function approveAll(Request $request)
    {
        return $this->billingService->approveAll($request->all());
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
            $cangooroo = $this->cangoorooService->updateCangoorooData($request->reserve, $request->cangooroo_booking_id, $request->cangooroo_service_id);
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
        $exportFile = UtilsExport::exportFile($request->all(), 'faturamento');

        (new BillingExport($request->all(), $approvalStatus, $exportFile['export']))->store($exportFile['path'], 's3', $exportFile['extension'] == '.xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV)->chain([
            new NotifyUserOfCompletedExport($exportFile['path'], $exportFile['export']),
        ]);

        return response()->json([
            'sucess' => $exportFile['export']->id
        ], 200);
    }

    public function exportBillingForApproval(Request $request)
    {
        $exportFile = UtilsExport::exportFile($request->all(), 'faturamentoAAprovar');

        (new BillingForApprovalExport($request->all(), $exportFile['export']))->store($exportFile['path'], 's3', $exportFile['extension'] == '.xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV)->chain([
            new NotifyUserOfCompletedExport($exportFile['path'], $exportFile['export']),
        ]);

        return response()->json([
            'sucess' => $exportFile['export']->id
        ], 200);
    }

    public function refreshStatuses($id)
    {
        return $this->billingService->refreshStatuses($id);
    }

    public function getBillingUsers()
    {
        return $this->billingService->getBillingUsers();
    }
}
