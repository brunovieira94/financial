<?php

namespace App\Http\Controllers;

use App\Exports\PurchaseOrderExport;
use Illuminate\Http\Request;
use App\Http\Requests\StorePurchaseOrderRequest;
use App\Http\Requests\PutPurchaseOrderRequest;
use App\Services\PurchaseOrderService as PurchaseOrderService;

class PurchaseOrderController extends Controller
{

    private $purchaseOrderService;

    public function __construct(PurchaseOrderService $purchaseOrderService)
    {
        $this->purchaseOrderService = $purchaseOrderService;
    }

    public function index(Request $request)
    {
        return $this->purchaseOrderService->getAllPurchaseOrder($request->all());
    }

    public function show($id)
    {
        return $this->purchaseOrderService->getPurchaseOrder($id);
    }

    public function store(StorePurchaseOrderRequest $request)
    {
        return $this->purchaseOrderService->postPurchaseOrder($request->all(), $request);
    }

    public function update(PutPurchaseOrderRequest $request, $id)
    {
        return $this->purchaseOrderService->putPurchaseOrder($id, $request->all(), $request);
    }

    public function destroy($id)
    {
        $purchaseOrder = $this->purchaseOrderService->deletePurchaseOrder($id);
        return response('');
    }

    public function listinvoice($id)
    {
        return $this->purchaseOrderService->getListInvoicePurchaseOrder($id);
    }

    public function getinvoice($id)
    {
        return $this->purchaseOrderService->getInvoicePurchaseOrder($id);
    }

    public function delivery(Request $request)
    {
        return $this->purchaseOrderService->putPurchaseOrderDelivery($request->all());
    }

    public function export(Request $request)
    {
        if (array_key_exists('exportFormat', $request->all())) {
            if ($request->all()['exportFormat'] == 'csv') {
                return (new PurchaseOrderExport($request->all()))->download('pedidosDeCompra.csv', \Maatwebsite\Excel\Excel::CSV, ['Content-Type' => 'text/csv']);
            }
        }
        return (new PurchaseOrderExport($request->all()))->download('pedidosDeCompra.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }
}
