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
use App\Models\GroupFormPayment;
use App\Models\PaymentRequestHasAttachments;
use Config;
use ZipStream\Option\Archive;

class PaymentRequestService
{
    private $paymentRequest;
    private $installments;
    private $tax;
    private $approvalFlow;
    private $groupFormPayment;
    private $attachments;


    private $with = ['attachments', 'group_payment', 'tax', 'approval', 'installments', 'provider', 'bank_account_provider', 'business', 'cost_center', 'chart_of_accounts', 'currency', 'user'];

    public function __construct(PaymentRequestHasAttachments $attachments,ApprovalFlow $approvalFlow, PaymentRequest $paymentRequest, PaymentRequestHasInstallments $installments, AccountsPayableApprovalFlow $approval, PaymentRequestHasTax $tax, GroupFormPayment $groupFormPayment)
    {
        $this->paymentRequest = $paymentRequest;
        $this->installments = $installments;
        $this->approval = $approval;
        $this->tax = $tax;
        $this->approvalFlow = $approvalFlow;
        $this->groupFormPayment = $groupFormPayment;
        $this->attachments = $attachments;
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
            $paymentRequestInfo['invoice_file'] = $this->storeArchive($request->invoice_file, 'invoice')[0];
        }
        if (array_key_exists('billet_file', $paymentRequestInfo)) {
            $paymentRequestInfo['billet_file'] = $this->storeArchive($request->billet_file, 'billet')[0];
        }
        if (array_key_exists('xml_file', $paymentRequestInfo)) {
            $paymentRequestInfo['xml_file'] = $this->storeArchive($request->xml_file, 'XML')[0];
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

        if (!array_key_exists('bank_account_provider_id', $paymentRequestInfo)) {
            $idBankProviderDefault = null;
            foreach (ProviderHasBankAccounts::where('provider_id', $paymentRequestInfo['provider_id'])->get() as $bank) {
            $idBankProviderDefault = $bank->bank_account_id;
            if ($bank->default_bank == true) {
                $idBankProviderDefault = $bank->bank_account_id;
                break;
            }
            $paymentRequestInfo['bank_account_provider_id'] = $idBankProviderDefault;
        }
        }

        $paymentRequest = new PaymentRequest;
        $paymentRequest = $paymentRequest->create($paymentRequestInfo);

        if (array_key_exists('attachments', $paymentRequestInfo)) {
            $arrayAttachments = $this->storeArchive($request->attachments, 'attachment-payment-request');
            $this->syncAttachments($arrayAttachments, $paymentRequest);
        }

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

        if ($approval->order == 0) {
            if ($approval->order >= $maxOrder) {
                $approval->status = 1;
            } else {
                $approval->order += 1;
            }
            $approval->reason_to_reject_id = null;
            $approval->reason = null;
        }
        /*if ($approval->order != 0) {
            if ($paymentRequestInfo['approve'] == "true") {
                if ($approval->order >= $maxOrder) {
                    $approval->status = 1;
                } else {
                    $approval->order += 1;
                }
                $approval->reason_to_reject_id = null;
                $approval->reason = null;
            }
        }*/

        $approval->save();
        activity()->enableLogging();

        if (array_key_exists('invoice_file', $paymentRequestInfo)) {
            $paymentRequestInfo['invoice_file'] = $this->storeArchive($request->invoice_file, 'invoice')[0];
        }
        if (array_key_exists('billet_file', $paymentRequestInfo)) {
             $paymentRequestInfo['billet_file'] = $this->storeArchive($request->billet_file, 'billet')[0];
        }
        if (array_key_exists('attachments', $paymentRequestInfo)) {
            $this->putAttachments($id, $paymentRequestInfo, $request);
        }
        if (array_key_exists('xml_file', $paymentRequestInfo)) {
             $paymentRequestInfo['xml_file'] = $this->storeArchive($request->xml_file, 'XML')[0];
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
            activity()->disableLogging();
            $approval->status = 3;
            $approval->save();
            activity()->enableLogging();
            return true;
        } else {
            return response()->json([
                'erro' => 'Só é permitido deletar conta na ordem 0',
            ], 422);
        }
    }

    public function storeArchive($archives, $folder)
    {
        $nameFiles = [];

        if (!is_array($archives)) {
            $archives = [
                $archives
            ];
        }

        foreach ($archives as $archive) {
            $generatedName = null;
            $data = uniqid(date('HisYmd'));

            if(is_array($archive)){
                $archive = $archive['attachment'];
            }
            $originalName  = explode('.', $archive->getClientOriginalName());
            $extension = $originalName[count($originalName) - 1];
            $generatedName = "{$originalName[0]}_{$data}.{$extension}";

            $upload = $archive->storeAs($folder, $generatedName);

            if (!$upload)
                return response('Falha ao realizar o upload do arquivo.', 500)->send();

            array_push($nameFiles, $generatedName);
        }

        return $nameFiles;
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

    public function getAllGroupFormPayment($requestInfo)
    {
        $groupPaymentRequest = Utils::search($this->groupFormPayment, $requestInfo);
        return Utils::pagination($groupPaymentRequest, $requestInfo);
    }

    public function syncAttachments($arrayAttachments, $paymentRequest)
    {
        foreach($arrayAttachments as $attachment)
        {
            PaymentRequestHasAttachments::create([
                'payment_request_id' => $paymentRequest->id,
                'attachment' => $attachment,
            ]);
        }
    }

    public function putAttachments($id, $paymentRequestInfo, Request $request)
    {
        $updateAttachments = [];
        $createdAttachments = [];
        $destroyCollection = [];

        if (array_key_exists('attachments_ids', $paymentRequestInfo))
        {
            $updateAttachments[] = $paymentRequestInfo['attachments_ids'];
        }

        foreach ($paymentRequestInfo['attachments'] as $key => $attachment)
        {
            $paymentRequestHasAttachment = new PaymentRequestHasAttachments;
            $attachment['attachment'] = $this->storeArchive($request->attachments[$key], 'attachment-payment-request')[0];
            $paymentRequestHasAttachment = $paymentRequestHasAttachment->create([
                'payment_request_id' => $id,
                'attachment' => $attachment['attachment'],
            ]);
            $createdAttachments[] = $paymentRequestHasAttachment->id;
        }

        $collection = $this->attachments->where('payment_request_id', $id)->whereNotIn('id', $updateAttachments)->whereNotIn('id', $createdAttachments)->get();
        foreach ($collection as $value)
        {
            $pushObject = [];
            $pushObject['id'] = $value['id'];
            array_push($destroyCollection, $pushObject);
        }
        $this->attachments->destroy($destroyCollection);
    }
}
