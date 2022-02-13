<?php

namespace App\Services;

use App\Models\PaymentRequest;
use App\Models\PaymentRequestHasInstallments;
use App\Models\AccountsPayableApprovalFlow;
use App\Models\PaymentRequestHasTax;
use App\Models\ProviderHasBankAccounts;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\ApprovalFlow;
use Config;

class PaymentRequestService
{
    private $paymentRequest;
    private $installments;
    private $tax;
    private $approvalFlow;

    private $with = ['tax', 'approval', 'installments', 'provider', 'bank_account_provider', 'business', 'cost_center', 'chart_of_accounts', 'currency', 'user'];

    public function __construct(ApprovalFlow $approvalFlow, PaymentRequest $paymentRequest, PaymentRequestHasInstallments $installments, AccountsPayableApprovalFlow $approval, PaymentRequestHasTax $tax)
    {
        $this->paymentRequest = $paymentRequest;
        $this->installments = $installments;
        $this->approval = $approval;
        $this->tax = $tax;
        $this->approvalFlow = $approvalFlow;
    }

    public function getAllPaymentRequest($requestInfo)
    {
        $paymentRequest = Utils::search($this->paymentRequest, $requestInfo);
        return Utils::pagination($paymentRequest->where('user_id', auth()->user()->id)->with($this->with), $requestInfo);
    }

    public function getPaymentRequest($id)
    {
        return $this->paymentRequest->with($this->with)->findOrFail($id);
    }

    public function postPaymentRequest(Request $request)
    {
        $paymentRequestInfo = $request->all();
        $paymentRequestInfo['user_id'] = auth()->user()->id;

        if (array_key_exists('invoice_file', $paymentRequestInfo)) {
            $paymentRequestInfo['invoice_file'] = $this->storeInvoice($request);
        }
        if (array_key_exists('billet_file', $paymentRequestInfo)) {
            $paymentRequestInfo['billet_file'] = $this->storeBillet($request);
        }
        if (array_key_exists('xml_file', $paymentRequestInfo)) {
            $paymentRequestInfo['xml_file'] = $this->storeXML($request);
        }
        if (!array_key_exists('form_payment', $paymentRequestInfo)) {
            $paymentRequestInfo['form_payment'] = '04'; //default code pix
        }
        if (!array_key_exists('bar_code', $paymentRequestInfo) && !array_key_exists('invoice_number', $paymentRequestInfo)) {
            $paymentRequestInfo['payment_type'] = 2;
        } elseif (array_key_exists('bar_code', $paymentRequestInfo)) {
            $paymentRequestInfo['payment_type'] = 1;
        } else {
            $paymentRequestInfo['payment_type'] = 0;
        }

        if (!array_key_exists('bar_code', $paymentRequestInfo)) {
            if (!array_key_exists('invoice_type', $paymentRequestInfo)) {
                if (array_key_exists('invoice_number', $paymentRequestInfo)) {
                    $invoiceType = DB::table('payment_requests')
                        ->select('invoice_type', DB::raw('count(invoice_type) as repeated'))
                        ->where('invoice_type', '<>', null)
                        ->groupBy('invoice_type')
                        ->orderBy('repeated', 'desc')
                        ->get();
                    $paymentRequestInfo['invoice_type'] = $invoiceType[0]->invoice_type ?? null;
                }
            }
        }

        $idBankProviderDefault = null;
        foreach (ProviderHasBankAccounts::where('provider_id', $paymentRequestInfo['provider_id'])->get() as $bank) {
            $idBankProviderDefault = $bank->bank_account_id;
            if ($bank->default_bank == true) {
                $idBankProviderDefault = $bank->bank_account_id;
                break;
            }
        }

        $paymentRequestInfo['bank_account_provider_id'] = $idBankProviderDefault;
        $paymentRequest = new PaymentRequest;
        $paymentRequest = $paymentRequest->create($paymentRequestInfo);

        $accountsPayableApprovalFlow = new AccountsPayableApprovalFlow;
        activity()->disableLogging();
        $accountsPayableApprovalFlow = $accountsPayableApprovalFlow->create([
            'payment_request_id' => $paymentRequest->id,
            'order' => 1,
            'status' => 0,
        ]);
        activity()->enableLogging();

        $this->syncTax($paymentRequest, $paymentRequestInfo);
        $this->syncInstallments($paymentRequest, $paymentRequestInfo, true, true);
        return $this->paymentRequest->with($this->with)->findOrFail($paymentRequest->id);
    }

    public function putPaymentRequest($id, Request $request)
    {
        $paymentRequestInfo = $request->all();
        $paymentRequest = $this->paymentRequest->findOrFail($id);
        $maxOrder = $this->approvalFlow->max('order');
        $approval = $this->approval->where('payment_request_id', $paymentRequest->id)->first();

        activity()->disableLogging();

        if ($paymentRequest->payment_type != 0) {
            if (array_key_exists('invoice_number', $paymentRequestInfo)) {
                if ($approval->status == 4) {
                    $approval->status = 7;
                }
            }
        }

        if ($approval->status != 7) {
            $approval->status = Config::get('constants.status.open');
        }

        if ($approval->order != 0) {
            if ($paymentRequestInfo['approve'] == "true") {
                if ($approval->order >= $maxOrder) {
                    $approval->status = 1;
                } else {
                    $approval->order += 1;
                }
                $approval->reason_to_reject_id = null;
                $approval->reason = null;
            }
        } else {
            $approval->order += 1;
        }

        $approval->save();
        activity()->enableLogging();

        if (array_key_exists('invoice_file', $paymentRequestInfo)) {
            $paymentRequestInfo['invoice_file'] = $this->storeInvoice($request);
        }
        if (array_key_exists('billet_file', $paymentRequestInfo)) {
            $paymentRequestInfo['billet_file'] = $this->storeBillet($request);
        }
        if (array_key_exists('xml_file', $paymentRequestInfo)) {
            $paymentRequestInfo['xml_file'] = $this->storeXML($request);
        }

        $paymentRequest->fill($paymentRequestInfo)->save();
        $this->putTax($id, $paymentRequestInfo);

        $updateCompetence = array_key_exists('competence_date', $paymentRequestInfo);
        $updateExtension = array_key_exists('extension_date', $paymentRequestInfo);

        $this->syncInstallments($paymentRequest, $paymentRequestInfo, $updateCompetence, $updateExtension);
        return $this->paymentRequest->with($this->with)->findOrFail($paymentRequest->id);
    }

    public function deletePaymentRequest($id)
    {
        $paymentRequest = $this->paymentRequest->findOrFail($id);
        $approval = $this->approval->where('payment_request_id', $paymentRequest->id)->first();


        if ($approval->order == 0 || ($approval->order == 1 && $approval->status == 0)) {
            $this->destroyInstallments($paymentRequest);
            $this->paymentRequest->findOrFail($id)->delete();
            return true;
        } else {
            return response()->json([
                'erro' => 'Só é permitido deletar conta na ordem 0',
            ], 422);
        }
    }

    public function storeXML(Request $request)
    {

        $nameFile = null;
        $data = uniqid(date('HisYmd'));

        $originalName  = explode('.', $request->xml_file->getClientOriginalName());
        $extension = $originalName[count($originalName) - 1];
        $nameFile = "{$originalName[0]}_{$data}.{$extension}";
        $uploadXML = $request->xml_file->storeAs('XML', $nameFile);

        if (!$uploadXML)
            return response('Falha ao realizar o upload do arquivo.', 500)->send();

        return $nameFile;
    }

    public function storeInvoice(Request $request)
    {

        $nameFile = null;
        $data = uniqid(date('HisYmd'));

        $originalName  = explode('.', $request->invoice_file->getClientOriginalName());
        $extension = $request->invoice_file->extension();
        $nameFile = "{$originalName[0]}_{$data}.{$extension}";
        $uploadInvoice = $request->invoice_file->storeAs('invoice', $nameFile);

        if (!$uploadInvoice)
            return response('Falha ao realizar o upload do arquivo.', 500)->send();

        return $nameFile;
    }

    public function storeBillet(Request $request)
    {

        $nameFile = null;
        $data = uniqid(date('HisYmd'));

        if ($request->hasFile('billet_file') && $request->file('billet_file')->isValid()) {

            $extension = $request->billet_file->extension();
            $originalName  = explode('.', $request->billet_file->getClientOriginalName());
            $nameFile = "{$originalName[0]}_{$data}.{$extension}";
            $uploadBillet = $request->billet_file->storeAs('billet', $nameFile);

            if (!$uploadBillet)
                return response('Falha ao realizar o upload do arquivo.', 500)->send();

            return $nameFile;
        }
    }

    public function syncInstallments($paymentRequest, $paymentRequestInfo, $updateCompetence, $updateExtension)
    {
        if (array_key_exists('installments', $paymentRequestInfo)) {
            $this->destroyInstallments($paymentRequest);
            foreach ($paymentRequestInfo['installments'] as $key => $installments) {
                $paymentRequestHasInstallments = new PaymentRequestHasInstallments;
                $installments['payment_request_id'] = $paymentRequest['id'];
                $installments['parcel_number'] = $key + 1;

                if ($updateCompetence) {
                    if (!array_key_exists('competence_date', $installments)) {
                        $date = new Carbon($installments['due_date']);
                        $date->subMonths(1);
                        $installments['competence_date'] = $date;
                    }
                }

                if ($updateExtension) {
                    if (!array_key_exists('extension_date', $installments)) {
                        $installments['extension_date'] = $installments['due_date'];
                    }
                }

                try {
                    $paymentRequestHasInstallments = $paymentRequestHasInstallments->create($installments);
                } catch (\Exception $e) {
                    $this->destroyInstallments($paymentRequest);
                    $this->paymentRequest->findOrFail($paymentRequest->id)->delete();
                    return response('Falha ao salvar as parcelas no banco de dados.', 500)->send();
                }
            }
        }
    }

    public function destroyInstallments($paymentRequest)
    {
        $collection = $this->installments->where('payment_request_id', $paymentRequest['id'])->get(['id']);
        $this->installments->destroy($collection->toArray());
    }

    public function syncTax($paymentRequest, $paymentRequestInfo)
    {
        if (array_key_exists('tax', $paymentRequestInfo)) {
            $this->destroyTax($paymentRequest);
            foreach ($paymentRequestInfo['tax'] as $key => $tax) {
                $paymentRequestHasTax = new PaymentRequestHasTax;
                $tax['payment_request_id'] = $paymentRequest['id'];
                $paymentRequestHasTax = $paymentRequestHasTax->create($tax);
            }
        }
    }

    public function destroyTax($paymentRequest)
    {
        $collection = $this->tax->where('payment_request_id', $paymentRequest['id'])->get(['id']);
        $this->tax->destroy($collection->toArray());
    }

    public function putTax($id, $paymentRequestInfo)
    {

        $updateTax = [];
        $createdTax = [];

        if (array_key_exists('tax', $paymentRequestInfo)) {
            foreach ($paymentRequestInfo['tax'] as $tax) {
                if (array_key_exists('id', $tax)) {
                    $paymentRequestHasTax = $this->tax->findOrFail($tax['id']);
                    $paymentRequestHasTax->fill($tax)->save();
                    $updateTax[] = $tax['id'];
                } else {
                    $paymentRequestHasTax = $this->tax->create([
                        'payment_request_id' => $id,
                        'type_of_tax_id' => $tax['type_of_tax_id'],
                        'tax_amount' => $tax['tax_amount'],
                    ]);
                    $createdTax[] = $paymentRequestHasTax->id;
                }
            }
        }

        $collection = $this->tax->where('payment_request_id', $id)->whereNotIn('id', $updateTax)->whereNotIn('id', $createdTax)->get(['id']);
        $this->tax->destroy($collection->toArray());
    }
}
