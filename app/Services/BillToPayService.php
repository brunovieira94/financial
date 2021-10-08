<?php

namespace App\Services;
use App\Models\BillToPay;
use App\Models\BillToPayHasInstallments;
use App\Models\AccountsPayableApprovalFlow;
use Illuminate\Http\Response;
use Illuminate\Http\Request;

class BillToPayService
{
    private $billToPay;
    private $installments;

    public function __construct(BillToPay $billToPay, BillToPayHasInstallments $installments)
    {
        $this->billToPay = $billToPay;
        $this->installments = $installments;
    }

    public function getAllBillToPay()
    {
        return $this->billToPay->with(['installments', 'provider', 'bankAccountProvider', 'bankAccountCompany', 'business', 'costCenter', 'chartOfAccounts', 'currency', 'user'])->get();
    }

    public function getBillToPay($id)
    {
        return $this->billToPay->with(['installments', 'provider', 'bankAccountProvider', 'bankAccountCompany', 'business', 'costCenter', 'chartOfAccounts', 'currency', 'user'])->findOrFail($id);
    }

    public function postBillToPay(Request $request)
    {
        $billToPayInfo = $request->all();

        $billToPayInfo['id_user'] = auth()->user()->id;

        if (array_key_exists('invoice_file', $billToPayInfo)){
            $billToPayInfo['invoice_file'] = self::storeInvoice($request);
        }
        if (array_key_exists('billet_file', $billToPayInfo)){
            $billToPayInfo['billet_file'] = self::storeBillet($request);
        }

        $billToPay = new BillToPay;
        $billToPay = $billToPay->create($billToPayInfo);
        $accountsPayableApprovalFlow = new AccountsPayableApprovalFlow;

        $accountsPayableApprovalFlow = $accountsPayableApprovalFlow->create([
            'id_bill_to_pay' => $billToPay->id,
            'order' => 0,
            'status' => 0,
        ]);

        self::syncInstallments($billToPay, $billToPayInfo);
        return $this->billToPay->with(['installments', 'provider', 'bankAccountProvider', 'bankAccountCompany', 'business', 'costCenter', 'chartOfAccounts', 'currency', 'user'])->findOrFail($billToPay->id);
    }

    public function putBillToPay($id, Request $request)
    {
        $billToPayInfo = $request->all();
        $billToPay = $this->billToPay->findOrFail($id);

        if (array_key_exists('invoice_file', $billToPayInfo)){
            $billToPayInfo['invoice_file'] = self::storeInvoice($request);
        }

        if (array_key_exists('billet_file', $billToPayInfo)){
            $billToPayInfo['billet_file'] = self::storeBillet($request);
        }

        $billToPay->fill($billToPayInfo)->save();
        self::syncInstallments($billToPay, $billToPayInfo);
        return $this->billToPay->with(['installments', 'provider', 'bankAccountProvider', 'bankAccountCompany', 'business', 'costCenter', 'chartOfAccounts', 'currency', 'user'])->findOrFail($billToPay->id);
    }

    public function deleteBillToPay($id)
    {
        $billToPay = $this->billToPay->findOrFail($id);
        self::destroyInstallments($billToPay);
        $this->billToPay->findOrFail($id)->delete();
        return true;
    }

    public function payInstallment($id){
        $installment = $this->installments->findOrFail($id);
        $installment->pay = true;
        $installment->save();
        return $this->installments->findOrFail($installment->id);
    }

    public function storeInvoice(Request $request){

        $nameFile = null;
        $data = uniqid(date('HisYmd'));

        $originalNameInvoice  = explode('.', $request->invoice_file->getClientOriginalName());
        $extensionInvoice = $request->invoice_file->extension();

        $nameFileInvoice = "{$originalNameInvoice[0]}_{$data}.{$extensionInvoice}";
        $uploadInvoice = $request->invoice_file->storeAs('invoice', $nameFileInvoice);

        if ( !$uploadInvoice )
             return error_log('Falha ao realizar o upload do arquivo.');

        return $nameFileInvoice;
    }

    public function storeBillet(Request $request){

        $nameFile = null;
        $data = uniqid(date('HisYmd'));

        if ($request->hasFile('billet_file') && $request->file('billet_file')->isValid()) {

            $extensionBillet = $request->billet_file->extension();
            $originalNameBillet  = explode('.' , $request->billet_file->getClientOriginalName());
            $nameFileBillet = "{$originalNameBillet[0]}_{$data}.{$extensionBillet}";
            $uploadBillet = $request->billet_file->storeAs('billet', $nameFileBillet);

            if ( !$uploadBillet )
                return error_log('Falha ao realizar o upload do arquivo.');
          return $nameFileBillet;
        }
    }

    public function syncInstallments($billToPay, $billToPayInfo)
    {
        if(array_key_exists('installments', $billToPayInfo)){

            self::destroyInstallments($billToPay);

            foreach($billToPayInfo['installments'] as $installments){
                $billToPayHasInstallments = new BillToPayHasInstallments;
                $installments['bill_to_pay'] = $billToPay['id'];
                $billToPayHasInstallments = $billToPayHasInstallments->create($installments);
            }
        }
    }

    public function destroyInstallments($billToPay)
    {
        $collection = $this->installments->where('bill_to_pay', $billToPay['id'])->get(['id']);
        $this->installments->destroy($collection->toArray());
    }

}
