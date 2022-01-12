<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreBillToPayRequest;
use App\Services\BillToPayService as BillToPayService;
use App\Http\Requests\PutBillToPayRequest;
use App\Imports\BillsToPayImport;

class BillToPayController extends Controller
{
    private $billToPayService;
    private $billToPayImport;

    public function __construct(BillToPayService $billToPayService, BillsToPayImport $billToPayImport)
    {
        $this->billToPayService = $billToPayService;
        $this->billToPayImport = $billToPayImport;
    }

    public function index(Request $request)
    {
        return $this->billToPayService->getAllBillToPay($request->all());
    }

    public function show($id)
    {
        return $this->billToPayService->getBillToPay($id);
    }

    public function store(StoreBillToPayRequest $request)
    {
        return $this->billToPayService->postBillToPay($request);
    }

    public function update(PutBillToPayRequest $request, $id)
    {
        return $this->billToPayService->putBillToPay($id, $request);
    }

    public function destroy($id)
    {
       $billToPay = $this->billToPayService->deleteBillToPay($id);
       return response('');
    }

    public function payInstallment($id)
    {
       return $this->billToPayService->payInstallment($id);
    }

    public function import()
    {
        $this->billToPayImport->import(request()->file('import_file'));
        return response('');
    }
}
