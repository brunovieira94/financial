<?php

namespace App\Services;

use App\Http\Resources\reports\RoutePaymentRequestAllResource;
use App\Jobs\SendAllAttachmentJob;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestHasInstallments;
use App\Models\AccountsPayableApprovalFlow;
use App\Models\PaymentRequestHasTax;
use App\Models\ProviderHasBankAccounts;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\ApprovalFlow;
use App\Models\AttachmentReport;
use App\Models\BankAccount;
use App\Models\CostCenter;
use App\Models\GroupFormPayment;
use App\Models\PaymentRequestClean;
use App\Models\PaymentRequestHasAttachments;
use App\Models\PaymentRequestHasInstallmentLinked;
use App\Models\PaymentRequestHasInstallmentsClean;
use App\Models\PaymentRequestHasPurchaseOrderInstallments;
use App\Models\PaymentRequestHasPurchaseOrders;
use App\Models\Provider;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDelivery;
use App\Models\PurchaseOrderHasInstallments;
use App\Models\PurchaseOrderHasServices;
use App\Models\TemporaryLogUploadPaymentRequest;
use App\Models\PurchaseOrderHasProducts;
use AWS\CRT\HTTP\Response;
use Config;
use Exception;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Aws\S3\ObjectUploader;
use Illuminate\Support\Facades\Storage;

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
    private $installmentClean;
    private $attachmentReport;
    private $approval;

    private $with = ['installments_linked.payment_request.provider', 'installments_linked.payment_request.company', 'installments_linked.payment_request.cost_center', 'currency_old', 'group_approval_flow', 'purchase_order.purchase_order', 'purchase_order.purchase_order_installments', 'company.bank_account', 'company.managers', 'attachments', 'group_payment.form_payment', 'tax.typeOfTax', 'approval.approval_flow', 'installments.bank_account_provider', 'installments.group_payment.form_payment', 'provider.bank_account', 'provider.provider_category', 'bank_account_provider', 'business', 'cost_center', 'chart_of_accounts', 'currency', 'user'];
    private $withLog = ['currency_old', 'cnab_payment_request', 'tax', 'bank_account_provider', 'company', 'approval', 'attachments', 'group_payment', 'purchase_order', 'group_approval_flow', 'installments', 'provider', 'bank_account_provider', 'business', 'cost_center', 'chart_of_accounts', 'currency', 'user'];
    private $installmentWith = ['group_payment.form_payment', 'payment_request.provider', 'payment_request.company', 'bank_account_provider', 'cnab_generated_installment.generated_cnab', 'payment_request.purchase_order.purchase_order_installments', 'payment_request.purchase_order.purchase_order'];

    public function __construct(AttachmentReport $attachmentReport, PaymentRequestHasInstallmentsClean $installmentClean, PaymentRequestClean $paymentRequestClean, PaymentRequestHasAttachments $attachments, ApprovalFlow $approvalFlow, PaymentRequest $paymentRequest, PaymentRequestHasInstallments $installments, AccountsPayableApprovalFlow $approval, PaymentRequestHasTax $tax, GroupFormPayment $groupFormPayment)
    {
        $this->paymentRequest = $paymentRequest;
        $this->installments = $installments;
        $this->approval = $approval;
        $this->tax = $tax;
        $this->approvalFlow = $approvalFlow;
        $this->groupFormPayment = $groupFormPayment;
        $this->attachments = $attachments;
        $this->paymentRequestClean = $paymentRequestClean;
        $this->installmentClean = $installmentClean;
        $this->attachmentReport = $attachmentReport;
    }

    public function getPaymentRequestByUser($requestInfo)
    {
        $paymentRequests = Utils::search($this->paymentRequestClean, $requestInfo);
        auth()->user()->id = auth()->user()->logged_user_id == null ? auth()->user()->id : auth()->user()->logged_user_id;
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
        return RoutePaymentRequestAllResource::collection(Utils::pagination($paymentRequests->withTrashed()->withoutGlobalScopes(), $requestInfo));
    }

    public function getPaymentRequest($id)
    {
        $paymentRequest = $this->paymentRequestClean->with($this->with)->withTrashed()->withoutGlobalScopes()->findOrFail($id);
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
        DB::beginTransaction();
        try {
            $paymentRequestInfo = $request->all();
            $paymentRequestInfo['user_id'] = auth()->user()->id;

            $paymentRequestInfo['group_approval_flow_id'] = CostCenter::findOrFail($paymentRequestInfo['cost_center_id'])->group_approval_flow_id;

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

            if (array_key_exists('attachments', $paymentRequestInfo)) {
                $arrayAttachments = $this->storeArchive($request->attachments, 'attachment-payment-request', $paymentRequest);
                $this->syncAttachments($arrayAttachments, $paymentRequest);
            }
            if (array_key_exists('invoice_file', $paymentRequestInfo)) {
                $paymentRequestInfo['invoice_file'] = $this->storeArchive($request->invoice_file, 'invoice', $paymentRequest)[0] ?? null;
            }
            if (array_key_exists('billet_file', $paymentRequestInfo)) {
                $paymentRequestInfo['billet_file'] = $this->storeArchive($request->billet_file, 'billet', $paymentRequest)[0] ?? null;
            }
            if (array_key_exists('xml_file', $paymentRequestInfo)) {
                $paymentRequestInfo['xml_file'] = $this->storeArchive($request->xml_file, 'XML', $paymentRequest)[0] ?? null;
            }

            activity()->disableLogging();
            $accountsPayableApprovalFlow = new AccountsPayableApprovalFlow;
            $accountsPayableApprovalFlow = $accountsPayableApprovalFlow->create([
                'payment_request_id' => $paymentRequest->id,
                'order' => 1,
                'status' => 0,
                'group_approval_flow_id' => $paymentRequest->group_approval_flow_id,
            ]);
            activity()->enableLogging();
            $paymentRequest->fill($paymentRequestInfo)->save();
            activity()->enableLogging();

            $this->syncPurchaseOrder($paymentRequest, $paymentRequestInfo);
            if ($paymentRequest->payment_type == 0) {
                $this->syncPurchaseOrderDelivery($paymentRequest, $paymentRequestInfo);
                $this->notifyUsers($paymentRequest, $paymentRequestInfo, auth()->user());
            }
            $this->syncTax($paymentRequest, $paymentRequestInfo);
            $this->syncInstallments($paymentRequest, $paymentRequestInfo, true, true, $request);
            $this->syncProviderGeneric($paymentRequestInfo);
            $this->syncInstallmentsLinked($paymentRequest, $paymentRequestInfo);
            Utils::createLogApprovalFlowLogPaymentRequest($paymentRequest->id, 'created', null, null, 0, $paymentRequestInfo['user_id'], null);
            DB::commit();
            return $this->paymentRequest->with($this->with)->findOrFail($paymentRequest->id);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Erro ao cadastrar a solicitação de pagamento, tente novamente.',
            ], 422);
        }
    }

    public function putPaymentRequest($id, Request $request)
    {
        $paymentRequestInfo = $request->all();
        $paymentRequest = $this->paymentRequest->with(['currency_old', 'cnab_payment_request', 'tax', 'bank_account_provider', 'company', 'approval', 'attachments', 'group_payment', 'purchase_order', 'group_approval_flow', 'installments', 'provider', 'bank_account_provider', 'business', 'cost_center', 'chart_of_accounts', 'currency', 'user'])->findOrFail($id);
        $paymentRequestOld = $this->paymentRequest->with($this->withLog)->findOrFail($id);
        $paymentRequest->edit_counter += 1;
        $maxOrder = $this->approvalFlow->where('group_approval_flow_id', $paymentRequest->group_approval_flow_id)->max('order');
        $approval = $this->approval->where('payment_request_id', $paymentRequest->id)->first();
        $stageAccount = $approval->order;

        activity()->disableLogging();
        if ($paymentRequest->payment_type != 0) {
            if (array_key_exists('invoice_number', $paymentRequestInfo)) {
                if ($approval->status == 4) {
                    $approval->status = 7;
                }
            }
        }

        if ($approval->status == 1) {
            $approval->order = $maxOrder;
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

        if ($paymentRequest->cost_center_id != $paymentRequestInfo['cost_center_id']) {
            $costCenter = CostCenter::findOrFail($paymentRequestInfo['cost_center_id']);
            if ($paymentRequest->group_form_payment_id != $costCenter->group_approval_flow_id) {
                $paymentRequest->group_approval_flow_id = $costCenter->group_approval_flow_id;
                $approval->group_approval_flow_id = $costCenter->group_approval_flow_id;
                $approval->order = 0;
                $approval->status = 0;
            }
        }
        $approval->save();
        activity()->enableLogging();

        if (array_key_exists('invoice_file', $paymentRequestInfo)) {
            $paymentRequestInfo['invoice_file'] = $this->storeArchive($request->invoice_file, 'invoice', $paymentRequest)[0] ?? null;
        }
        if (array_key_exists('attachments', $paymentRequestInfo)) {
            $this->putAttachments($id, $paymentRequestInfo, $request);
        }
        if (array_key_exists('xml_file', $paymentRequestInfo)) {
            $paymentRequestInfo['xml_file'] = $this->storeArchive($request->xml_file, 'XML', $paymentRequest)[0] ?? null;
        }
        if (array_key_exists('billet_file', $paymentRequestInfo)) {
            $paymentRequestInfo['billet_file'] = $this->storeArchive($request->billet_file, 'billet', $paymentRequest)[0] ?? null;
        }
        $this->putTax($id, $paymentRequestInfo);

        $updateCompetence = array_key_exists('competence_date', $paymentRequestInfo);
        $updateExtension = array_key_exists('extension_date', $paymentRequestInfo);

        $this->syncPurchaseOrder($paymentRequest, $paymentRequestInfo, $id);
        if ($paymentRequest->payment_type == 0) {
            $this->syncPurchaseOrderDelivery($paymentRequest, $paymentRequestInfo, $id);
        }
        $this->syncInstallments($paymentRequest, $paymentRequestInfo, $updateCompetence, $updateExtension, $request);
        $this->syncProviderGeneric($paymentRequestInfo, $id);

        activity()->disableLogging();
        $paymentRequest->fill($paymentRequestInfo)->save();
        activity()->enableLogging();
        $this->syncInstallmentsLinked($paymentRequest, $paymentRequestInfo);

        $paymentRequestNew = $this->paymentRequest->with($this->withLog)->findOrFail($id);
        Utils::createManualLogPaymentRequest($paymentRequestOld, $paymentRequestNew, auth()->user()->id, $this->paymentRequest);
        Utils::createLogApprovalFlowLogPaymentRequest($paymentRequest->id, 'updated', null, null, $stageAccount, auth()->user()->id, null, null, $approval->order);
        return $this->paymentRequest->with($this->with)->findOrFail($paymentRequest->id);
    }

    public function deletePaymentRequest($id)
    {
        $paymentRequest = $this->paymentRequest->findOrFail($id);
        $approval = $this->approval->where('payment_request_id', $paymentRequest->id)->first();

        if ($approval->order == 0 || ($approval->order == 1 && $approval->status == 0 || auth()->user()->role->id == 1)) {
            $this->paymentRequest->findOrFail($id)->delete();
            activity()->disableLogging();
            $approval->status = 3;
            $approval->save();
            activity()->enableLogging();
            Utils::createLogApprovalFlowLogPaymentRequest($paymentRequest->id, 'deleted', null, null, $approval->order, auth()->user()->id, null, null, $approval->order);

            if ($paymentRequest->payment_type == 0) {
                $purchaseOrdersDelveryDelete = [];

                if (PurchaseOrderDelivery::where(['payment_request_id' => $paymentRequest->id])->exists()) {
                    foreach (PurchaseOrderDelivery::where('payment_request_id', $paymentRequest->id)->get() as $purchaseOrderDelivery) {
                        array_push($purchaseOrdersDelveryDelete, $purchaseOrderDelivery->id);
                    }
                }
                PurchaseOrderDelivery::destroy($purchaseOrdersDelveryDelete);
            }

            return true;
        } else {
            return response()->json([
                'error' => 'Só é permitido deletar conta na ordem 0',
            ], 422);
        }
    }

    public function storeArchive($archives, $folder, $paymentRequest, $parcelNumber = null)
    {
        $nameFiles = [];
        if (!is_array($archives)) {
            $archives = [
                $archives
            ];
        }

        $type = ($paymentRequest->payment_type == 0) ? 'NF_' : (($paymentRequest->payment_type == 3) ? 'IN_' : (($paymentRequest->payment_type == 2) ? 'AV_' : (($paymentRequest->payment_type == 1) ? 'BL_' : null)));
        if ($parcelNumber == null) {
            $newNameArchive = $type . $paymentRequest->id;
        } else {
            $newNameArchive = $type . $paymentRequest->id . '_' . $parcelNumber;
        }

        foreach ($archives as $archive) {

            $generatedName = null;
            $data = uniqid(date('HisYmd'));
            if (is_array($archive)) {
                $archive = $archive['attachment'];
            }
            $originalName  = explode('.', $archive->getClientOriginalName());
            $extension = $originalName[count($originalName) - 1];
            $generatedName = "{$newNameArchive}_{$data}.{$extension}";
            //$upload = $archive->storeAs($folder, $generatedName);
            $s3Client = new S3Client([
                'region' => env('AWS_DEFAULT_REGION'),
                'version' => '2006-03-01'
            ]);
            $bucket = env('AWS_BUCKET');
            $key = $folder . '/' . $generatedName;
            try {
                // Using stream instead of file path
                $source = fopen($archive, 'rb');
                $uploader = new ObjectUploader(
                    $s3Client,
                    $bucket,
                    $key,
                    $source
                );
                $uploader->upload();
                array_push($nameFiles, $generatedName);
            } catch (Exception $e) {
                TemporaryLogUploadPaymentRequest::create([
                    'error' => $e->getMessage(),
                    'folder' => $folder
                ]);
                error_log($e->getMessage());
            }
        }
        return $nameFiles;
    }

    public function syncInstallments($paymentRequest, $paymentRequestInfo, $updateCompetence, $updateExtension, Request $request)
    {
        if (array_key_exists('installments', $paymentRequestInfo)) {
            $notDeleteInstallmentsID = [];
            foreach ($paymentRequestInfo['installments'] as $key => $installments) {
                if (array_key_exists('status', $installments) && $installments['status'] != 4) {
                    $installments['status'] = 0;
                }
                if (array_key_exists('billet_file', $installments)) {
                    $installments['billet_file'] = $this->storeArchive($request->installments[$key]['billet_file'], 'billet', $paymentRequest, $key)[0] ?? null;
                }
                if (array_key_exists('portion_amount', $installments)) {
                    if ($installments['portion_amount'] <= 0) {
                        $installments['portion_amount'] = $installments['initial_value'];
                    }
                } else {
                    $installments['portion_amount'] = $installments['initial_value'];
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
                    if (array_key_exists('portion_amount', $installments)) {
                        if ($installments['portion_amount'] <= 0) {
                            $installments['portion_amount'] = $installments['initial_value'];
                        }
                    } else {
                        $installments['portion_amount'] = $installments['initial_value'];
                    }
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
                    $verificationPeriod = null;
                    if (array_key_exists('verification_period', $installments)) {
                        $verificationPeriod = $installments['verification_period'];
                        unset($installments['verification_period']);
                    }
                    $paymentRequestHasInstallments = $paymentRequestHasInstallments->create($installments);
                    $notDeleteInstallmentsID[] = $paymentRequestHasInstallments->id;
                    activity()->disableLogging();
                    $installment = PaymentRequestHasInstallmentsClean::findOrFail($paymentRequestHasInstallments->id);
                    $installment->verification_period = $verificationPeriod;
                    $installment->save();
                    activity()->enableLogging();
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
        $groupFormPayment = $this->groupFormPayment;
        $groupPaymentRequest = Utils::search($groupFormPayment, $requestInfo);
        $groupPaymentRequest = Utils::baseFilterGroupFormPayment($groupPaymentRequest, $requestInfo);
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
            foreach ($paymentRequestInfo['attachments_ids'] as $key => $attachment) {
                $updateAttachments[] = $attachment;
            }
        }

        foreach ($paymentRequestInfo['attachments'] as $key => $attachment) {
            $paymentRequestHasAttachment = new PaymentRequestHasAttachments;
            $attachment['attachment'] = $this->storeArchive($request->attachments[$key], 'attachment-payment-request', PaymentRequestClean::findOrFail($id))[0] ?? null;
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
        $paymentRequest = PaymentRequest::with('approval')->findOrFail($requestInfo['payment_request_id']);
        $paymentRequestOld = $this->paymentRequest->with($this->withLog)->findOrFail($requestInfo['payment_request_id']);

        activity()->disableLogging();
        if (auth()->user()->role_id != 1) {
            if (ApprovalFlow::where('role_id', auth()->user()->role_id)
                ->where('order', $paymentRequest->approval->order)
                ->where('group_approval_flow_id',  $paymentRequest->group_approval_flow_id)->exists()
            ) {
                $approvalFlow = ApprovalFlow::where('role_id', auth()->user()->role_id)
                    ->where('order', $paymentRequest->approval->order)
                    ->where('group_approval_flow_id',  $paymentRequest->group_approval_flow_id)
                    ->first();
            } else {
                return response()->json([
                    'error' => 'Permissão não localizada.'
                ], 422);
            }
            if (!$approvalFlow->extension) {
                return response()->json([
                    'error' => 'Não foi possível atualizar as informações da conta. Verifique as permissões e a etapa em que a conta está.'
                ], 422);
            }
        }

        foreach ($requestInfo['installments'] as $installment) {
            $paymentRequestHasInstallments = PaymentRequestHasInstallments::findOrFail($installment['id']);
            $paymentRequestHasInstallments->fill($installment)->save();
        }
        activity()->enableLogging();

        $paymentRequestNew = $this->paymentRequest->with($this->withLog)->findOrFail($requestInfo['payment_request_id']);
        Utils::createManualLogPaymentRequest($paymentRequestOld, $paymentRequestNew, auth()->user()->id, $this->paymentRequest);
        Utils::createLogApprovalFlowLogPaymentRequest($paymentRequest->id, 'updated', null, null, $paymentRequest->approval->order, auth()->user()->id, null, null, $paymentRequest->approval->order);
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

                if ($paymentRequestInfo['payment_type'] == 0) {
                    //update status delivery
                    foreach (PurchaseOrderHasProducts::where('purchase_order_id', $purchaseOrders['order'])->get() as $porchaseOrderHasProductDelivery) {
                        PurchaseOrderDelivery::create(
                            [
                                'payment_request_id' => $paymentRequest->id,
                                'purchase_order_id' => $purchaseOrders['order'],
                                'product_id' => $porchaseOrderHasProductDelivery->product_id,
                                'delivery_quantity' => 0,
                                'quantity' => $porchaseOrderHasProductDelivery->quantity,
                                'status' => 0
                            ]
                        );
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
        $paymentRequestOld = $this->paymentRequest->with($this->withLog)->findOrFail($installment->payment_request_id);
        if (array_key_exists('billet_file', $requestInfo)) {
            $requestInfo['billet_file'] = $this->storeArchive($request->billet_file, 'billet', PaymentRequestClean::findOrFail($installment->payment_request_id), $installment->parcel_number)[0] ?? null;
        }
        if (array_key_exists('status', $requestInfo) && $requestInfo['status'] != 4) {
            $requestInfo['status'] = 0;
        }
        $installment->fill($requestInfo)->save();

        $paymentRequest = PaymentRequest::with('approval')->findOrFail($installment->payment_request_id);
        Utils::createLogApprovalFlowLogPaymentRequest($paymentRequest->id, 'updated', null, null, $paymentRequest->approval->order, auth()->user()->id, null, null, $paymentRequest->approval->order);
        $paymentRequestNew = $this->paymentRequest->with($this->withLog)->findOrFail($installment->payment_request_id);
        Utils::createManualLogPaymentRequest($paymentRequestOld, $paymentRequestNew, auth()->user()->id, $this->paymentRequest);
        return $this->installments->with(['payment_request', 'group_payment', 'bank_account_provider'])->findOrFail($id);
    }

    public function syncProviderGeneric($requestInfo, $id = null)
    {
        $provider = Provider::findOrFail($requestInfo['provider_id']);
        if (array_key_exists('installments', $requestInfo)) {
            if ($provider->generic_provider) {
                foreach ($requestInfo['installments'] as $installment) {
                    if ($installment['group_form_payment_id'] != 1) {
                        if (array_key_exists('bank_account_provider_id', $installment) && $installment['bank_account_provider_id'] != null) {
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
    public function getInstallment($id)
    {
        return $this->installmentClean->with($this->installmentWith)->findOrFail($id);
    }

    public function attachment($requestInfo)
    {
        $requestInfo['attachment-id'] = AttachmentReport::create([
            'from' => $requestInfo['from'],
            'to' => $requestInfo['to'],
            'mails' => implode(', ', $requestInfo['mails']),
            'user_id' => auth()->user()->id
        ])->id;
        SendAllAttachmentJob::dispatch($requestInfo);

        return Response()->json([
            'Sucesso' => 'Processo finalizado.'
        ], 200);
    }

    public function getAttachment($requestInfo)
    {
        $attachment = Utils::search($this->attachmentReport, $requestInfo);
        return Utils::pagination($attachment->with('user')->where('created_at', '>', Carbon::now()->subDays(7)), $requestInfo);
    }

    public function syncPurchaseOrderDelivery($paymentRequest, $paymentRequestInfo, $id = null)
    {
        if (array_key_exists('purchase_orders', $paymentRequestInfo)) {

            $purchaseOrdersDelveryDelete = [];
            if ($id != null) {
                if (PurchaseOrderDelivery::where(['payment_request_id' => $id])->exists()) {
                    foreach (PurchaseOrderDelivery::where('payment_request_id', $id)->get() as $purchaseOrderDelivery) {
                        array_push($purchaseOrdersDelveryDelete, $purchaseOrderDelivery->id);
                    }
                }
            }

            foreach ($paymentRequestInfo['purchase_orders'] as $purchaseOrders) {
                foreach (PurchaseOrderHasProducts::where('purchase_order_id', $purchaseOrders['order'])->get() as $getProductsInfo) {
                    PurchaseOrderDelivery::create([
                        'payment_request_id' => $paymentRequest->id,
                        'purchase_order_id' =>  $purchaseOrders['order'],
                        'product_id' => $getProductsInfo->product_id,
                        'delivery_quantity' => 0,
                        'quantity' => $getProductsInfo->quantity,
                        'status' => 0
                    ]);
                }

                foreach (PurchaseOrderHasServices::where('purchase_order_id', $purchaseOrders['order'])->get() as $getServicesInfo) {
                    PurchaseOrderDelivery::create([
                        'payment_request_id' => $paymentRequest->id,
                        'purchase_order_id' =>  $purchaseOrders['order'],
                        'service_id' => $getServicesInfo->service_id,
                        'delivery_quantity' => 0,
                        'quantity' => $getServicesInfo->quantity,
                        'status' => 0
                    ]);
                }
            }
            PurchaseOrderDelivery::destroy($purchaseOrdersDelveryDelete);
        } else if ($id != null) {
            $purchaseOrdersDelveryDelete = [];

            if (PurchaseOrderDelivery::where(['payment_request_id' => $id])->exists()) {
                foreach (PurchaseOrderDelivery::where('payment_request_id', $id)->get() as $purchaseOrderDelivery) {
                    array_push($purchaseOrdersDelveryDelete, $purchaseOrderDelivery->id);
                }
            }
            PurchaseOrderDelivery::destroy($purchaseOrdersDelveryDelete);
        }
    }

    public function syncInstallmentsLinked($paymentRequest, $paymentRequestInfo)
    {
        if (array_key_exists('installments_linked', $paymentRequestInfo)) {

            $idsInstallments = [];

            foreach ($paymentRequestInfo['installments_linked'] as $installment) {
                if (array_key_exists('id', $installment)) {
                    if (!PaymentRequestHasInstallmentLinked::where('payment_requests_installment_id', $installment['id'])->where('payment_request_id', '!=', $paymentRequest->id)->exists() && !PaymentRequestHasInstallmentsClean::where('payment_request_id', $paymentRequest->id)->where('id', $installment['id'])->exists()) {
                        array_push($idsInstallments, $installment['id']);
                    }
                }
            }

            $installmentLinked = PaymentRequestHasInstallmentLinked::where('payment_request_id', $paymentRequest->id)->get('payment_requests_installment_id');
            PaymentRequestHasInstallmentLinked::where('payment_request_id', $paymentRequest->id)->delete();
            DB::table('payment_requests_installments')->whereIn('id', $installmentLinked->pluck('payment_requests_installment_id')->toArray())->update(
                [
                    'linked' => false,
                    'status' => 0
                ]
            );
            $paymentRequest->installments_linked()->sync($idsInstallments);
            DB::table('payment_requests_installments')->whereIn('id', $idsInstallments)->update(
                [
                    'linked' => true,
                    'status' => $paymentRequest->approval->status == 4 ? 4 : 0,
                ]
            );
        } else {
            $installmentLinked = PaymentRequestHasInstallmentLinked::where('payment_request_id', $paymentRequest->id)->get('payment_requests_installment_id');
            PaymentRequestHasInstallmentLinked::where('payment_request_id', $paymentRequest->id)->delete();
            DB::table('payment_requests_installments')->whereIn('id', $installmentLinked->pluck('payment_requests_installment_id')->toArray())->update(
                [
                    'linked' => false,
                    'status' => 0
                ]
            );
        }
    }

    public function notifyUsers($paymentRequest, $paymentRequestInfo, $approverUser)
    {
        if (array_key_exists('purchase_orders', $paymentRequestInfo)) {
            foreach ($paymentRequestInfo['purchase_orders'] as $purchaseOrder) {
                $usersMail = [];
                $purchaseOrderInfo = PurchaseOrder::where('id', $purchaseOrder['order'])->firstOrFail();
                NotificationService::generateDataSendRedisPurchaseOrderPaymentRequest($paymentRequest, $purchaseOrderInfo, $usersMail, 'Nota fiscal vinculada ao pedido de compra', 'purchase-order-vinculated', $approverUser);
            }
        }
    }
}
