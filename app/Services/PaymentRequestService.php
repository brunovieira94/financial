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
use App\Models\BankAccount;
use App\Models\GroupFormPayment;
use App\Models\PaymentRequestClean;
use App\Models\PaymentRequestHasAttachments;
use App\Models\PaymentRequestHasPurchaseOrderInstallments;
use App\Models\PaymentRequestHasPurchaseOrders;
use App\Models\Provider;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderHasInstallments;
use AWS\CRT\HTTP\Response;
use Config;
use Exception;
use ZipStream\Option\Archive;

use function PHPUnit\Framework\isNull;

class PaymentRequestService
{
    private $paymentRequest;
    private $installments;
    private $tax;
    private $approvalFlow;
    private $groupFormPayment;
    private $attachments;
    private $paymentRequestClean;


    private $with = ['purchase_order.purchase_order','purchase_order.purchase_order_installments', 'company', 'attachments', 'group_payment', 'tax', 'approval.approval_flow', 'installments.bank_account_provider', 'installments.group_payment.form_payment', 'provider', 'bank_account_provider', 'business', 'cost_center', 'chart_of_accounts', 'currency', 'user'];

    public function __construct(PaymentRequestClean $paymentRequestClean, PaymentRequestHasAttachments $attachments, ApprovalFlow $approvalFlow, PaymentRequest $paymentRequest, PaymentRequestHasInstallments $installments, AccountsPayableApprovalFlow $approval, PaymentRequestHasTax $tax, GroupFormPayment $groupFormPayment)
    {
        $this->paymentRequest = $paymentRequest;
        $this->installments = $installments;
        $this->approval = $approval;
        $this->tax = $tax;
        $this->approvalFlow = $approvalFlow;
        $this->groupFormPayment = $groupFormPayment;
        $this->attachments = $attachments;
        $this->paymentRequestClean = $paymentRequestClean;
    }

    public function getPaymentRequestByUser($requestInfo)
    {
        $paymentRequests = Utils::search($this->paymentRequestClean, $requestInfo);
        $paymentRequests = Utils::pagination($paymentRequests->with(['provider', 'currency'])->where('user_id', auth()->user()->id), $requestInfo);
        /*foreach ($paymentRequests as $paymentRequest) {
            foreach ($paymentRequest->purchase_order as $purchaseOrder) {
                foreach ($purchaseOrder->purchase_order_installments as $key => $installment) {
                    $installment = [
                        'id' => $installment->installment_purchase->id,
                        'amount_received' => $installment->amount_received,
                        'purchase_order_id' => $installment->installment_purchase->purchase_order_id,
                        'parcel_number' => $installment->installment_purchase->parcel_number,
                        'portion_amount' => $installment->installment_purchase->portion_amount,
                        'due_date' => $installment->installment_purchase->due_date,
                        'note' => $installment->installment_purchase->note,
                        'percentage_discount' => $installment->installment_purchase->percentage_discount,
                        'money_discount' => $installment->installment_purchase->money_discount,
                        'invoice_received' => $installment->installment_purchase->invoice_received,
                        'invoice_paid' => $installment->installment_purchase->invoice_paid,
                        'payment_request_id' => $installment->installment_purchase->payment_request_id,
                        'amount_paid' => $installment->installment_purchase->amount_paid,
                    ];
                    $purchaseOrder->purchase_order_installments[$key] = $installment;
                }
            }
        }*/
        return $paymentRequests;
    }

    public function getAllPaymentRequest($requestInfo)
    {
        $paymentRequests = Utils::search($this->paymentRequestClean, $requestInfo);
        $paymentRequests = Utils::pagination($paymentRequests->with(['provider', 'currency']), $requestInfo);
        /*foreach ($paymentRequests as $paymentRequest) {
            foreach ($paymentRequest->purchase_order as $purchaseOrder) {
                foreach ($purchaseOrder->purchase_order_installments as $key => $installment) {
                    $installment = [
                        'id' => $installment->installment_purchase->id,
                        'amount_received' => $installment->amount_received,
                        'purchase_order_id' => $installment->installment_purchase->purchase_order_id,
                        'parcel_number' => $installment->installment_purchase->parcel_number,
                        'portion_amount' => $installment->installment_purchase->portion_amount,
                        'due_date' => $installment->installment_purchase->due_date,
                        'note' => $installment->installment_purchase->note,
                        'percentage_discount' => $installment->installment_purchase->percentage_discount,
                        'money_discount' => $installment->installment_purchase->money_discount,
                        'invoice_received' => $installment->installment_purchase->invoice_received,
                        'invoice_paid' => $installment->installment_purchase->invoice_paid,
                        'payment_request_id' => $installment->installment_purchase->payment_request_id,
                        'amount_paid' => $installment->installment_purchase->amount_paid,
                    ];
                    $purchaseOrder->purchase_order_installments[$key] = $installment;
                }
            }
        }*/
        return $paymentRequests;
    }

    public function getPaymentRequest($id)
    {
        $paymentRequest = $this->paymentRequestClean->with($this->with)->withTrashed()->findOrFail($id);
        foreach ($paymentRequest->purchase_order as $purchaseOrder) {
            foreach ($purchaseOrder->purchase_order_installments as $key => $installment) {
                $installment = [
                    'id' => $installment->installment_purchase->id,
                    'amount_received' => $installment->amount_received,
                    'purchase_order_id' => $installment->installment_purchase->purchase_order_id,
                    'parcel_number' => $installment->installment_purchase->parcel_number,
                    'portion_amount' => $installment->installment_purchase->portion_amount,
                    'due_date' => $installment->installment_purchase->due_date,
                    'note' => $installment->installment_purchase->note,
                    'percentage_discount' => $installment->installment_purchase->percentage_discount,
                    'money_discount' => $installment->installment_purchase->money_discount,
                    'invoice_received' => $installment->installment_purchase->invoice_received,
                    'invoice_paid' => $installment->installment_purchase->invoice_paid,
                    'payment_request_id' => $installment->installment_purchase->payment_request_id,
                    'amount_paid' => $installment->installment_purchase->amount_paid,
                ];
                $purchaseOrder->purchase_order_installments[$key] = $installment;
            }
        }
        return $paymentRequest;
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

        if (array_key_exists('attachments', $paymentRequestInfo)) {
            $arrayAttachments = $this->storeArchive($request->attachments, 'attachment-payment-request');
            $this->syncAttachments($arrayAttachments, $paymentRequest);
        }

        $this->syncPurchaseOrder($paymentRequest, $paymentRequestInfo);
        $this->syncTax($paymentRequest, $paymentRequestInfo);
        $this->syncInstallments($paymentRequest, $paymentRequestInfo, true, true, $request);
        $this->syncProviderGeneric($paymentRequestInfo);
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

        $approval->save();
        activity()->enableLogging();

        if (array_key_exists('invoice_file', $paymentRequestInfo)) {
            $paymentRequestInfo['invoice_file'] = $this->storeArchive($request->invoice_file, 'invoice')[0];
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

        $this->syncPurchaseOrder($paymentRequest, $paymentRequestInfo, $id);
        $this->syncInstallments($paymentRequest, $paymentRequestInfo, $updateCompetence, $updateExtension, $request);
        $this->syncProviderGeneric($paymentRequestInfo, $id);
        return $this->paymentRequest->with($this->with)->findOrFail($paymentRequest->id);
    }

    public function deletePaymentRequest($id)
    {
        $paymentRequest = $this->paymentRequest->findOrFail($id);
        $approval = $this->approval->where('payment_request_id', $paymentRequest->id)->first();

        if ($approval->order == 0 || ($approval->order == 1 && $approval->status == 0)) {
            $this->paymentRequest->findOrFail($id)->delete();
            activity()->disableLogging();
            $approval->status = 3;
            $approval->save();
            activity()->enableLogging();
            return true;
        } else {
            return response()->json([
                'error' => 'Só é permitido deletar conta na ordem 0',
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

            if (is_array($archive)) {
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

    public function syncInstallments($paymentRequest, $paymentRequestInfo, $updateCompetence, $updateExtension, Request $request)
    {
        if (array_key_exists('installments', $paymentRequestInfo)) {
            $notDeleteInstallmentsID = [];
            foreach ($paymentRequestInfo['installments'] as $key => $installments) {

                if (array_key_exists('billet_file', $installments)) {
                    $installments['billet_file'] = $this->storeArchive($request->installments[$key]['billet_file'], 'billet')[0];
                }

                if (array_key_exists('id', $installments)) {
                    $installments['parcel_number'] = $key + 1;
                    $installmentBD = PaymentRequestHasInstallments::findOrFail($installments['id']);
                    $installmentBD->fill($installments)->save();
                    $notDeleteInstallmentsID[] = $installments['id'];
                } else {
                    $paymentRequestHasInstallments = new PaymentRequestHasInstallments;
                    $installments['payment_request_id'] = $paymentRequest['id'];
                    $installments['parcel_number'] = $key + 1;
                    $installments['status'] = 0;

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
                    $paymentRequestHasInstallments = $paymentRequestHasInstallments->create($installments);
                    $notDeleteInstallmentsID[] = $paymentRequestHasInstallments->id;
                }
            }
            self::destroyInstallments($notDeleteInstallmentsID, $paymentRequest['id']);
        }
    }

    public function destroyInstallments($notDeleteInstallmentsID, $paymentRequestID)
    {
        $collection = $this->installments
            ->whereNotIn('id', $notDeleteInstallmentsID)
            ->where('payment_request_id', $paymentRequestID)
            ->get(['id']);

        $this->installments->destroy($collection->pluck('id'));
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
        foreach ($arrayAttachments as $attachment) {
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

        if (array_key_exists('attachments_ids', $paymentRequestInfo)) {
            $updateAttachments[] = $paymentRequestInfo['attachments_ids'];
        }

        foreach ($paymentRequestInfo['attachments'] as $key => $attachment) {
            $paymentRequestHasAttachment = new PaymentRequestHasAttachments;
            $attachment['attachment'] = $this->storeArchive($request->attachments[$key], 'attachment-payment-request')[0];
            $paymentRequestHasAttachment = $paymentRequestHasAttachment->create([
                'payment_request_id' => $id,
                'attachment' => $attachment['attachment'],
            ]);
            $createdAttachments[] = $paymentRequestHasAttachment->id;
        }

        $collection = $this->attachments->where('payment_request_id', $id)->whereNotIn('id', $updateAttachments)->whereNotIn('id', $createdAttachments)->get();
        foreach ($collection as $value) {
            $pushObject = [];
            $pushObject['id'] = $value['id'];
            array_push($destroyCollection, $pushObject);
        }
        $this->attachments->destroy($destroyCollection);
    }

    public function updateDateInstallment($requestInfo)
    {
        $permissionChange = true;
        $paymentRequest = PaymentRequest::with('approval')->findOrFail($requestInfo['payment_request_id']);
        $approvalFlow = ApprovalFlow::where('role_id', auth()->user()->role_id)
            ->where('order', $paymentRequest->approval->order)
            ->first();

        foreach ($requestInfo['installments'] as $installment) {
            if (array_key_exists('extension_date', $installment)) {
                if ($approvalFlow->extension == false) {
                    $permissionChange = false;
                    break;
                }
            }
        }

        if (!$permissionChange) {
            return response()->json([
                'error' => 'Não foi possível atualizar as informações da conta. Verifique as permissões e a etapa em que a conta está.'
            ], 422);
        }

        foreach ($requestInfo['installments'] as $installment) {
            $paymentRequestHasInstallments = PaymentRequestHasInstallments::findOrFail($installment['id']);
            $paymentRequestHasInstallments->fill($installment)->save();
        }

        return response()->json([
            'sucesso' => 'Os dados foram atualizados com sucesso.'
        ], 200);
    }

    public function syncPurchaseOrder($paymentRequest, $paymentRequestInfo, $id = null)
    {
        if (array_key_exists('purchase_orders', $paymentRequestInfo)) {

            $paymentRequestPurchaseOrderInstallmentsIDsDelete = [];
            $paymentRequestHasPurchaseOrdersIDsDelete = [];

            if ($id != null) {
                if (PaymentRequestHasPurchaseOrderInstallments::where('payment_request_id', $id)->exists()) {
                    foreach (PaymentRequestHasPurchaseOrderInstallments::where('payment_request_id', $id)->get() as $paymentHasPurchaseOrder) {
                        array_push($paymentRequestPurchaseOrderInstallmentsIDsDelete, $paymentHasPurchaseOrder->id);
                        $installmentPurchaseOrder = PurchaseOrderHasInstallments::findOrFail($paymentHasPurchaseOrder->purchase_order_has_installments_id);
                        $installmentPurchaseOrder->amount_paid -= $paymentHasPurchaseOrder->amount_received;
                        $installmentPurchaseOrder->save();
                    }
                }
                if (PaymentRequestHasPurchaseOrders::where('payment_request_id', $id)->exists()) {
                    foreach (PaymentRequestHasPurchaseOrders::where('payment_request_id', $id)->get() as $paymentHasPurchaseOrder) {
                        array_push($paymentRequestHasPurchaseOrdersIDsDelete, $paymentHasPurchaseOrder->id);
                    }
                }
            }

            foreach ($paymentRequestInfo['purchase_orders'] as $purchaseOrders) {
                $reviewed = false;
                if (PaymentRequestHasPurchaseOrders::where('payment_request_id', $paymentRequest->id)->where('purchase_order_id', $purchaseOrders['order'])->exists()) {
                    $reviewed = PaymentRequestHasPurchaseOrders::where('payment_request_id', $paymentRequest->id)->where('purchase_order_id', $purchaseOrders['order'])->first()->reviewed;
                }
                $paymentRequestHasPurchaseOrders = PaymentRequestHasPurchaseOrders::create(
                    [
                        'payment_request_id' => $paymentRequest->id,
                        'purchase_order_id' => $purchaseOrders['order'],
                        'reviewed' => $reviewed
                    ]
                );
                $purchaseOrder = PurchaseOrder::with('installments')
                    ->findOrFail($purchaseOrders['order']);
                $purchaseInstallmentsIDs = $purchaseOrder->installments->pluck('id')->toArray();
                foreach ($paymentRequestInfo['installment_purchase_order'] as $purchaseInstallment) {
                    if (in_array((int) $purchaseInstallment['installment'], $purchaseInstallmentsIDs)) {
                        PaymentRequestHasPurchaseOrderInstallments::create(
                            [
                                'payment_request_id' => $paymentRequest->id,
                                'purchase_order_has_installments_id' => $purchaseInstallment['installment'],
                                'payment_request_has_purchase_order_id' => $paymentRequestHasPurchaseOrders->id,
                                'amount_received' => isset($purchaseInstallment['amount_received']) ? $purchaseInstallment['amount_received'] : 0,
                            ]
                        );
                        $purchaseInstallment = PurchaseOrderHasInstallments::findOrFail($purchaseInstallment['installment']);
                        $amountPaid = DB::table('payment_request_has_purchase_order_installments')
                            ->where('purchase_order_has_installments_id', $purchaseInstallment->id)
                            ->whereNotIn('id', $paymentRequestPurchaseOrderInstallmentsIDsDelete)
                            ->sum('amount_received');
                        $purchaseInstallment->amount_paid = $amountPaid;
                        $purchaseInstallment->save();
                    }
                }
            }

            PaymentRequestHasPurchaseOrders::destroy($paymentRequestHasPurchaseOrdersIDsDelete);
            PaymentRequestHasPurchaseOrderInstallments::destroy($paymentRequestPurchaseOrderInstallmentsIDsDelete);
        } else if ($id != null) {
            $paymentRequestPurchaseOrderInstallmentsIDsDelete = [];
            $paymentRequestHasPurchaseOrdersIDsDelete = [];

            if (PaymentRequestHasPurchaseOrderInstallments::where('payment_request_id', $id)->exists()) {
                foreach (PaymentRequestHasPurchaseOrderInstallments::where('payment_request_id', $id)->get() as $paymentHasPurchaseOrder) {
                    array_push($paymentRequestPurchaseOrderInstallmentsIDsDelete, $paymentHasPurchaseOrder->id);
                    $installmentPurchaseOrder = PurchaseOrderHasInstallments::findOrFail($paymentHasPurchaseOrder->purchase_order_has_installments_id);
                    $installmentPurchaseOrder->amount_paid -= $paymentHasPurchaseOrder->amount_received;
                    $installmentPurchaseOrder->save();
                }
            }
            if (PaymentRequestHasPurchaseOrders::where('payment_request_id', $id)->exists()) {
                foreach (PaymentRequestHasPurchaseOrders::where('payment_request_id', $id)->get() as $paymentHasPurchaseOrder) {
                    array_push($paymentRequestHasPurchaseOrdersIDsDelete, $paymentHasPurchaseOrder->id);
                }
            }
            PaymentRequestHasPurchaseOrders::destroy($paymentRequestHasPurchaseOrdersIDsDelete);
            PaymentRequestHasPurchaseOrderInstallments::destroy($paymentRequestPurchaseOrderInstallmentsIDsDelete);
        }
    }

    public function updateInstallment($id, $request)
    {
        $requestInfo = $request->all();
        $installment = $this->installments->findOrFail($id);
        if (array_key_exists('billet_file', $requestInfo)) {
            $requestInfo['billet_file'] = $this->storeArchive($request->billet_file, 'billet')[0];
        }
        $installment->fill($requestInfo)->save();
        return $this->installments->with(['payment_request', 'group_payment', 'bank_account_provider'])->findOrFail($id);
    }

    public function syncProviderGeneric($requestInfo, $id = null)
    {
        $provider = Provider::findOrFail($requestInfo['provider_id']);
        if (array_key_exists('installments', $requestInfo)) {
            if ($provider->generic_provider) {
                foreach ($requestInfo['installments'] as $installment) {
                    if ($installment['group_form_payment_id'] != 1) {
                        $bankAccount = BankAccount::findOrFail($installment['bank_account_provider_id']);
                        $bankAccount->hidden = true;
                        $bankAccount->save();
                        $providerHasBankAccount = ProviderHasBankAccounts::create(
                            [
                                'provider_id' => $provider->id,
                                'bank_account_id' => $installment['bank_account_provider_id'],
                                'default_bank' => false
                            ]
                        );
                    }
                }
            }
        }
    }
}
