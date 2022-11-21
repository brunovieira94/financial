<?php

namespace App\Services;

use App\Models\ApprovalFlowSupply;
use App\Models\Module;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestHasPurchaseOrders;
use App\Models\Product;
use App\Models\ProviderQuotation;
use App\Models\ProviderQuotationHasProducts;
use App\Models\ProviderQuotationHasServices;
use App\Models\ProviderQuotationItems;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderClean;
use App\Models\PurchaseOrderDelivery;
use App\Models\PurchaseOrderHasProducts;
use App\Models\PurchaseOrderHasCompanies;
use App\Models\PurchaseOrderHasServices;
use App\Models\PurchaseOrderHasCostCenters;
use App\Models\PurchaseOrderHasPurchaseRequests;
use App\Models\PurchaseOrderHasAttachments;
use App\Models\PurchaseOrderServicesHasInstallments;
use App\Models\PurchaseOrderHasInstallments;
use App\Models\SupplyApprovalFlow;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestHasProducts;
use App\Models\RoleHasModule;
use Illuminate\Http\Request;

class PurchaseOrderService
{
    private $purchaseOrder;
    private $purchaseRequest;
    private $purchaseRequestHasProducts;
    private $purchaseOrderHasProducts;
    private $purchaseOrderHasCompanies;
    private $purchaseOrderHasServices;
    private $purchaseOrderHasCostCenters;
    private $purchaseOrderHasPurchaseRequests;
    private $purchaseOrderServicesHasInstallments;
    private $purchaseOrderHasInstallments;
    private $attachments;
    private $paymentRequestHasPurchaseOrders;
    private $paymentRequest;
    private $purchaseOrderClean;

    private $with = ['user', 'installments', 'approval', 'cost_centers', 'attachments', 'services', 'products', 'company', 'currency', 'provider', 'purchase_requests'];

    public function __construct(PurchaseOrderClean $purchaseOrderClean, PurchaseOrder $purchaseOrder, PurchaseRequest $purchaseRequest, PurchaseRequestHasProducts $purchaseRequestHasProducts, PurchaseOrderHasProducts $purchaseOrderHasProducts, PurchaseOrderHasCompanies $purchaseOrderHasCompanies, PurchaseOrderHasServices $purchaseOrderHasServices, PurchaseOrderHasCostCenters $purchaseOrderHasCostCenters, PurchaseOrderHasAttachments $attachments, PurchaseOrderServicesHasInstallments $purchaseOrderServicesHasInstallments, PurchaseOrderHasPurchaseRequests $purchaseOrderHasPurchaseRequests, PurchaseOrderHasInstallments $purchaseOrderHasInstallments, PaymentRequestHasPurchaseOrders $paymentRequestHasPurchaseOrders, PaymentRequest $paymentRequest)
    {
        $this->purchaseOrder = $purchaseOrder;
        $this->purchaseOrderClean = $purchaseOrderClean;
        $this->purchaseRequest = $purchaseRequest;
        $this->purchaseOrderHasProducts = $purchaseOrderHasProducts;
        $this->purchaseRequestHasProducts = $purchaseRequestHasProducts;
        $this->purchaseOrderHasCompanies = $purchaseOrderHasCompanies;
        $this->purchaseOrderHasServices = $purchaseOrderHasServices;
        $this->purchaseOrderHasCostCenters = $purchaseOrderHasCostCenters;
        $this->purchaseOrderHasPurchaseRequests = $purchaseOrderHasPurchaseRequests;
        $this->attachments = $attachments;
        $this->purchaseOrderServicesHasInstallments = $purchaseOrderServicesHasInstallments;
        $this->purchaseOrderHasInstallments = $purchaseOrderHasInstallments;
        $this->paymentRequestHasPurchaseOrders = $paymentRequestHasPurchaseOrders;
        $this->paymentRequest = $paymentRequest;
    }

    public function getAllPurchaseOrder($requestInfo)
    {
        $purchaseOrder = Utils::search($this->purchaseOrder, $requestInfo);

        if (auth()->user()->role->filter_cost_center_supply) {
            $purchaseOrderIds = [];
            foreach (auth()->user()->cost_center as $userCostCenter) {
                $purchaseOrderCostCenters = PurchaseOrderHasCostCenters::where('cost_center_id', $userCostCenter->id)->get(['purchase_order_id']);
                foreach ($purchaseOrderCostCenters as $purchaseOrderCostCenter) {
                    $purchaseOrderIds[] = $purchaseOrderCostCenter->purchase_order_id;
                }
            }
            $purchaseOrder->whereIn('id', $purchaseOrderIds);
        }

        $purchaseOrder = Utils::baseFilterPurchaseOrder($purchaseOrder, $requestInfo);

        return Utils::pagination($purchaseOrder->with($this->with), $requestInfo);
    }

    public function getPurchaseOrder($id)
    {
        $getPurchaseOrder = $this->purchaseOrder->with($this->with)->findOrFail($id);
        //soma produtos = ((unitary_value * quantity) - money_discount) - money_discount_products(purchase)
        $sumProducts = PurchaseOrderHasProducts::where('purchase_order_id', $id)
            ->sum(\DB::raw('unitary_value * quantity - money_discount'));
        $finalSumProducts = $sumProducts - $getPurchaseOrder['money_discount_products'];

        // soma serviçes
        $sumServices = PurchaseOrderHasServices::where('purchase_order_id', $id)
            ->sum(\DB::raw('unitary_value * quantity - money_discount'));
        $finalSumServices = $sumServices - $getPurchaseOrder['money_discount_services'];

        $finalTotal = $finalSumProducts + $finalSumServices;

        $getPurchaseOrder['final_total_value'] = $finalTotal;

        return $getPurchaseOrder;
    }

    public function postPurchaseOrder($purchaseOrderInfo, Request $request)
    {
        $purchaseOrder = new PurchaseOrder;
        $purchaseOrderInfo['user_id'] = auth()->user()->id;
        $purchaseOrderInfo['installments_total_value'] = $purchaseOrderInfo['final_negotiated_total_value'];
        $purchaseOrder = $purchaseOrder->create($purchaseOrderInfo);
        $this->syncProducts($purchaseOrder, $purchaseOrderInfo);
        $this->syncServices($purchaseOrder, $purchaseOrderInfo);
        $this->syncCompanies($purchaseOrder, $purchaseOrderInfo);
        $this->syncCostCenters($purchaseOrder, $purchaseOrderInfo);
        $this->syncAttachments($purchaseOrder, $purchaseOrderInfo, $request);
        $this->syncPurchaseRequests($purchaseOrder, $purchaseOrderInfo);

        $supplyApprovalFlow = new SupplyApprovalFlow;
        activity()->disableLogging();
        $supplyApprovalFlow = $supplyApprovalFlow->create([
            'id_purchase_order' => $purchaseOrder->id,
            'order' => 1,
            'status' => 0,
        ]);
        activity()->enableLogging();

        $this->syncInstallments($purchaseOrder, $purchaseOrderInfo);

        if (array_key_exists('quotation_id', $purchaseOrderInfo)) {
            $finalStatus = $this->getPurchaseOrderStatus($purchaseOrderInfo);

            ProviderQuotation::where('id', $purchaseOrderInfo['quotation_id'])->update(['status' => $finalStatus]);
            $providerQuotationId = ProviderQuotation::where('id', $purchaseOrderInfo['quotation_id'])->first();
            if ($providerQuotationId != null) {
                PurchaseRequest::whereIn('id', $providerQuotationId->request_ids)->update(['status' => $finalStatus + 1]);
            }

            if ($finalStatus == 2) {
                ProviderQuotationItems::where('provider_quotation_id', $purchaseOrderInfo['quotation_id'])->update([
                    'block_purchase_order' => true
                ]);
            }
        }

        if (array_key_exists('quotation_item_id', $purchaseOrderInfo)) {
            ProviderQuotationItems::where('id', $purchaseOrderInfo['quotation_item_id'])->update(['block_purchase_order' => true]);
        }

        return $this->purchaseOrder->with($this->with)->findOrFail($purchaseOrder->id);
    }

    public function getCanEditPurchaseOrder($route)
    {
        $getModule = Module::where('route', $route)->first();
        if ($getModule != null) {
            if (auth()->user()->role_id != 1) {
                $checkUserRoleModule = RoleHasModule::where([
                    'role_id' => auth()->user()->role_id,
                    'module_id' => $getModule->id
                ])->first();
                return $checkUserRoleModule != null && $checkUserRoleModule->update;
            } else {
                return true;
            }
        }
    }

    public function putPurchaseOrder($id, $purchaseOrderInfo, Request $request)
    {
        $newStatus = '';
        if ($this->getCanEditPurchaseOrder('purchase-order')) {
            $purchaseOrder = $this->purchaseOrder->with(['approval'])->findOrFail($id);
            //caso o pedido já foi aprovado só pode aprovar se tiver a permissão
            if ($purchaseOrder->approval->status == 1) {
                if ($this->getCanEditPurchaseOrder('approved-purchase-order')) {
                    $newStatus = 0;
                } else {
                    return response()->json([
                        'error' => 'Não é permitido ao usuário editar o pedido ' . $id . ', porque já está aprovado.',
                    ], 422);
                }
            }

            if (!array_key_exists('payment_condition', $purchaseOrderInfo)) {
                $purchaseOrderInfo['payment_condition'] = null;
            }
            if (!array_key_exists('billing_date', $purchaseOrderInfo)) {
                $purchaseOrderInfo['billing_date'] = null;
            }

            $approvedTotalValue = $purchaseOrder->approved_total_value;
            $approvedInstallmentValue = $purchaseOrder->approved_installment_value ?? $this->getPurchaseOrderValue($id);

            $purchaseOrderInfo['installments_total_value'] = $purchaseOrderInfo['final_negotiated_total_value'];
            $purchaseOrder->fill($purchaseOrderInfo)->save();
            $this->putProducts($id, $purchaseOrderInfo);
            $this->putServices($id, $purchaseOrderInfo);

            // caso o valor for maior do que o Aprovado o pedido deve voltar para o início da aprovação

            if ((($purchaseOrderInfo['negotiated_total_value'] > $approvedInstallmentValue) && ($purchaseOrderInfo['negotiated_total_value'] > $approvedTotalValue)) || $purchaseOrderInfo['final_negotiated_total_value'] > $approvedInstallmentValue) {
                $supplyApprovalFlow = SupplyApprovalFlow::find($purchaseOrder->approval['id']);
                $supplyApprovalFlow['order'] = 1;
                if ($newStatus != '') {
                    $supplyApprovalFlow['status'] = $newStatus;
                }
                $supplyApprovalFlow->save();
            }

            $this->putCompanies($id, $purchaseOrderInfo);
            $this->putCostCenters($id, $purchaseOrderInfo);
            $this->putAttachments($id, $purchaseOrderInfo, $request);

            $this->syncInstallments($purchaseOrder, $purchaseOrderInfo);

            return $this->purchaseOrder->with($this->with)->findOrFail($purchaseOrder->id);
        } else {
            return response()->json([
                'error' => 'Não é permitido ao usuário editar o pedido ' . $id,
            ], 422);
        }
    }

    public function deletePurchaseOrder($id)
    {
        $q_item_id = $this->purchaseOrder->findOrFail($id);

        $this->purchaseOrder->findOrFail($id)->delete();

        if ($q_item_id->quotation_id != null) {

            $purchaseOrderInfo = ['quotation_id' => $q_item_id->quotation_id];

            $finalStatus = $this->getPurchaseOrderStatus($purchaseOrderInfo);

            ProviderQuotation::where('id', $purchaseOrderInfo['quotation_id'])->update(['status' => $finalStatus - 1]);
            $providerQuotationId = ProviderQuotation::where('id', $purchaseOrderInfo['quotation_id'])->first();
            if ($providerQuotationId != null) {
                PurchaseRequest::whereIn('id', $providerQuotationId->request_ids)->update(['status' => $finalStatus]);
            }
        }

        if ($q_item_id->quotation_item_id != null) {
            ProviderQuotationItems::where('id', $q_item_id->quotation_item_id)->update(['block_purchase_order' => false]);
        }

        return true;
    }

    public function syncProducts($purchaseOrder, $purchaseOrderInfo)
    {
        if (array_key_exists('products', $purchaseOrderInfo)) {
            foreach ($purchaseOrderInfo['products'] as $product) {
                $purchaseOrderHasProducts = new PurchaseOrderHasProducts;
                $purchaseOrderHasProducts = $purchaseOrderHasProducts->create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_id' => $product['product_id'],
                    'unitary_value' => $product['unitary_value'],
                    'quantity' => $product['quantity'],
                    'percentage_discount' => $product['percentage_discount'],
                    'money_discount' => $product['money_discount'],
                ]);
            }
        }
    }

    public function putProducts($id, $purchaseOrderInfo)
    {
        $updateProducts = [];
        $createdProducts = [];
        if (array_key_exists('products', $purchaseOrderInfo)) {
            foreach ($purchaseOrderInfo['products'] as $product) {
                if (array_key_exists('id', $product)) {
                    $purchaseOrderHasProducts = $this->purchaseOrderHasProducts->findOrFail($product['id']);
                    $purchaseOrderHasProducts->fill($product)->save();
                    $updateProducts[] = $product['id'];
                } else {
                    $purchaseOrderHasProducts = $this->purchaseOrderHasProducts->create([
                        'purchase_order_id' => $id,
                        'product_id' => $product['product_id'],
                        'unitary_value' => $product['unitary_value'],
                        'quantity' => $product['quantity'],
                        'percentage_discount' => $product['percentage_discount'],
                        'money_discount' => $product['money_discount'],
                    ]);
                    $createdProducts[] = $purchaseOrderHasProducts->id;
                }
            }
            $collection = $this->purchaseOrderHasProducts->where('purchase_order_id', $id)->whereNotIn('id', $updateProducts)->whereNotIn('id', $createdProducts)->get(['id']);
            $this->purchaseOrderHasProducts->destroy($collection->toArray());
        }
    }

    public function syncServices($purchaseOrder, $purchaseOrderInfo)
    {
        if (array_key_exists('services', $purchaseOrderInfo)) {
            foreach ($purchaseOrderInfo['services'] as $service) {
                $purchaseOrderHasServices = new PurchaseOrderHasServices;
                $purchaseOrderHasServices = $purchaseOrderHasServices->create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'service_id' => $service['service_id'],
                    'unitary_value' => $service['unitary_value'],
                    'initial_date' => $service['initial_date'],
                    'end_date' => $service['end_date'],
                    'quantity' => $service['quantity'],
                    'automatic_renovation' => $service['automatic_renovation'],
                    'notice_time_to_renew' => $service['notice_time_to_renew'],
                    'percentage_discount' => $service['percentage_discount'],
                    'money_discount' => $service['money_discount'],
                    'contract_time' => $service['contract_time'],
                    'contract_frequency' => $service['contract_frequency'],
                ]);
                // $this->syncInstallments($purchaseOrderHasServices, $service);
            }
        }
    }

    public function putServices($id, $purchaseOrderInfo)
    {
        $updateServices = [];
        $createdServices = [];
        if (array_key_exists('services', $purchaseOrderInfo)) {
            foreach ($purchaseOrderInfo['services'] as $service) {
                if (array_key_exists('id', $service)) {
                    $purchaseOrderHasServices = $this->purchaseOrderHasServices->findOrFail($service['id']);
                    $purchaseOrderHasServices->fill($service)->save();
                    $updateServices[] = $service['id'];
                } else {
                    $purchaseOrderHasServices = $this->purchaseOrderHasServices->create([
                        'purchase_order_id' => $id,
                        'service_id' => $service['service_id'],
                        'unitary_value' => $service['unitary_value'],
                        'initial_date' => $service['initial_date'],
                        'end_date' => $service['end_date'],
                        'quantity' => $service['quantity'],
                        'automatic_renovation' => $service['automatic_renovation'],
                        'notice_time_to_renew' => $service['notice_time_to_renew'],
                        'percentage_discount' => $service['percentage_discount'],
                        'money_discount' => $service['money_discount'],
                        'contract_time' => $service['contract_time'],
                        'contract_frequency' => $service['contract_frequency'],
                    ]);
                    $createdServices[] = $purchaseOrderHasServices->id;
                }
                // $this->syncInstallments($purchaseOrderHasServices, $service);
            }
            $collection = $this->purchaseOrderHasServices->where('purchase_order_id', $id)->whereNotIn('id', $updateServices)->whereNotIn('id', $createdServices)->get(['id']);
            $this->purchaseOrderHasServices->destroy($collection->makeHidden(['end_contract_date'])->toArray());
        }
    }

    public function syncInstallments($purchaseOrder, $purchaseOrderInfo)
    {
        $updateInstallments = [];
        $createdInstallments = [];
        if (array_key_exists('installments', $purchaseOrderInfo)) {
            foreach ($purchaseOrderInfo['installments'] as $key => $installments) {
                if (array_key_exists('id', $installments)) {
                    $purchaseOrderHasInstallments = $this->purchaseOrderHasInstallments->findOrFail($installments['id']);
                    $purchaseOrderHasInstallments->fill($installments)->save();
                    $updateInstallments[] = $installments['id'];
                } else {
                    $installments['purchase_order_id'] = $purchaseOrder['id'];
                    $installments['parcel_number'] = $key + 1;
                    $purchaseOrderHasInstallments = $this->purchaseOrderHasInstallments->create($installments);
                    $createdInstallments[] = $purchaseOrderHasInstallments->id;
                }
            }
            $collection = $this->purchaseOrderHasInstallments->where('purchase_order_id', $purchaseOrder['id'])->whereNotIn('id', $updateInstallments)->whereNotIn('id', $createdInstallments)->get(['id']);
            $this->purchaseOrderHasInstallments->destroy($collection->toArray());
        }
    }

    public function syncCostCenters($purchaseOrder, $purchaseOrderInfo)
    {
        if (array_key_exists('cost_centers', $purchaseOrderInfo)) {
            foreach ($purchaseOrderInfo['cost_centers'] as $costCenter) {
                $purchaseOrderHasCostCenters = new PurchaseOrderHasCostCenters;
                $purchaseOrderHasCostCenters = $purchaseOrderHasCostCenters->create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'cost_center_id' => $costCenter['cost_center_id'],
                    'percentage' => $costCenter['percentage'],
                ]);
            }
        }
    }

    public function putCostCenters($id, $purchaseOrderInfo)
    {

        $updateCostCenters = [];
        $createdCostCenters = [];

        if (array_key_exists('cost_centers', $purchaseOrderInfo)) {
            foreach ($purchaseOrderInfo['cost_centers'] as $costCenter) {
                if (array_key_exists('id', $costCenter)) {
                    $purchaseOrderHasCostCenters = $this->purchaseOrderHasCostCenters->findOrFail($costCenter['id']);
                    $purchaseOrderHasCostCenters->fill($costCenter)->save();
                    $updateCostCenters[] = $costCenter['id'];
                } else {
                    $purchaseOrderHasCostCenters = $this->purchaseOrderHasCostCenters->create([
                        'purchase_order_id' => $id,
                        'cost_center_id' => $costCenter['cost_center_id'],
                        'percentage' => $costCenter['percentage'],
                    ]);
                    $createdCostCenters[] = $purchaseOrderHasCostCenters->id;
                }
            }
        }

        $collection = $this->purchaseOrderHasCostCenters->where('purchase_order_id', $id)->whereNotIn('id', $updateCostCenters)->whereNotIn('id', $createdCostCenters)->get(['id']);
        $this->purchaseOrderHasCostCenters->destroy($collection->toArray());
    }

    public function syncPurchaseRequests($purchaseOrder, $purchaseOrderInfo)
    {
        if (array_key_exists('purchase_requests', $purchaseOrderInfo)) {
            $productQuantityInOrder = [];
            foreach ($this->purchaseOrderHasProducts->where('purchase_order_id', $purchaseOrder->id)->get() as $purchaseOrderHasProducts) {
                $productQuantityInOrder[$purchaseOrderHasProducts['product_id']] = $productQuantityInOrder[$purchaseOrderHasProducts['product_id']] ?? 0;
                $productQuantityInOrder[$purchaseOrderHasProducts['product_id']] += $purchaseOrderHasProducts['quantity'];
            }
            foreach ($purchaseOrderInfo['purchase_requests'] as $purchaseRequest) {
                $purchaseOrderHasPurchaseRequests = new PurchaseOrderHasPurchaseRequests;
                $purchaseOrderHasPurchaseRequests = $purchaseOrderHasPurchaseRequests->create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'purchase_request_id' => $purchaseRequest['purchase_request_id'],
                ]);
                $purchaseRequestToUpdate = $this->purchaseRequest->find($purchaseRequest['purchase_request_id']);
                if ($purchaseRequestToUpdate) {
                    $purchaseRequestToUpdate->status = 1;
                    $isPartial = false;
                    foreach ($this->purchaseRequestHasProducts->where('purchase_request_id', $purchaseRequest['purchase_request_id'])->get() as $purchaseRequestHasProducts) {
                        if (array_key_exists($purchaseRequestHasProducts['product_id'], $productQuantityInOrder)) {
                            if ($productQuantityInOrder[$purchaseRequestHasProducts['product_id']] >= ($purchaseRequestHasProducts['quantity'] - $purchaseRequestHasProducts['in_order'])) {
                                $productQuantityInOrder[$purchaseRequestHasProducts['product_id']] -= ($purchaseRequestHasProducts['quantity'] - $purchaseRequestHasProducts['in_order']);
                                $purchaseRequestHasProducts->in_order = $purchaseRequestHasProducts['quantity'];
                                $purchaseRequestHasProducts->save();
                            } else {
                                $isPartial = true;
                                $purchaseRequestHasProducts->in_order += $productQuantityInOrder[$purchaseRequestHasProducts['product_id']];
                                $productQuantityInOrder[$purchaseRequestHasProducts['product_id']] = 0;
                                $purchaseRequestHasProducts->save();
                            }
                        }
                    }
                    if ($isPartial) {
                        $purchaseRequestToUpdate->status = 2;
                    }
                    $purchaseRequestToUpdate->save();
                }
            }
        }
    }

    public function syncCompanies($purchaseOrder, $purchaseOrderInfo)
    {
        if (array_key_exists('companies', $purchaseOrderInfo)) {
            foreach ($purchaseOrderInfo['companies'] as $company) {
                $purchaseOrderHasCompanies = new PurchaseOrderHasCompanies;
                $purchaseOrderHasCompanies = $purchaseOrderHasCompanies->create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'company_id' => $company['company_id'],
                ]);
            }
        }
    }

    public function putCompanies($id, $purchaseOrderInfo)
    {

        $updateCompanies = [];
        $createdCompanies = [];

        if (array_key_exists('companies', $purchaseOrderInfo)) {
            foreach ($purchaseOrderInfo['companies'] as $company) {
                if (array_key_exists('id', $company)) {
                    $purchaseOrderHasCompanies = $this->purchaseOrderHasCompanies->findOrFail($company['id']);
                    $purchaseOrderHasCompanies->fill($company)->save();
                    $updateCompanies[] = $company['id'];
                } else {
                    $purchaseOrderHasCompanies = $this->purchaseOrderHasCompanies->create([
                        'purchase_order_id' => $id,
                        'company_id' => $company['company_id'],
                    ]);
                    $createdCompanies[] = $purchaseOrderHasCompanies->id;
                }
            }
        }

        $collection = $this->purchaseOrderHasCompanies->where('purchase_order_id', $id)->whereNotIn('id', $updateCompanies)->whereNotIn('id', $createdCompanies)->get(['id']);
        $this->purchaseOrderHasCompanies->destroy($collection->toArray());
    }

    public function syncAttachments($purchaseOrder, $purchaseOrderInfo, Request $request)
    {
        if (array_key_exists('attachments', $purchaseOrderInfo)) {
            foreach ($purchaseOrderInfo['attachments'] as $key => $attachment) {
                $purchaseOrderHasAttachments = new PurchaseOrderHasAttachments;
                $attachment['attachment'] = $this->storeAttachment($request, $key);
                $purchaseOrderHasAttachments = $purchaseOrderHasAttachments->create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'attachment' => $attachment['attachment'],
                ]);
            }
        }
    }

    public function putAttachments($id, $purchaseOrderInfo, Request $request)
    {

        $updateAttachments = [];
        $createdAttachments = [];
        $destroyCollection = [];

        if (array_key_exists('attachments_ids', $purchaseOrderInfo)) {
            foreach ($purchaseOrderInfo['attachments_ids'] as $key => $attachment) {
                $updateAttachments[] = $attachment;
            }
        }

        if (array_key_exists('attachments', $purchaseOrderInfo)) {
            foreach ($purchaseOrderInfo['attachments'] as $key => $attachment) {
                $purchaseOrderHasAttachments = new PurchaseOrderHasAttachments;
                $attachment['attachment'] = $this->storeAttachment($request, $key);
                $purchaseOrderHasAttachments = $purchaseOrderHasAttachments->create([
                    'purchase_order_id' => $id,
                    'attachment' => $attachment['attachment'],
                ]);
                $createdAttachments[] = $purchaseOrderHasAttachments->id;
            }
        }

        $collection = $this->attachments->where('purchase_order_id', $id)->whereNotIn('id', $updateAttachments)->whereNotIn('id', $createdAttachments)->get();
        foreach ($collection as $value) {
            $pushObject = [];
            $pushObject['id'] = $value['id'];
            array_push($destroyCollection, $pushObject);
        }
        $this->attachments->destroy($destroyCollection);
    }

    public function storeAttachment(Request $request, $key)
    {
        $data = uniqid(date('HisYmd'));

        if ($request->hasFile('attachments.' . $key . '.attachment') && $request->file('attachments.' . $key . '.attachment')->isValid()) {
            $extensionAttachment = $request['attachments.' . $key . '.attachment']->extension();
            $originalNameAttachment  = explode('.', $request['attachments.' . $key . '.attachment']->getClientOriginalName());
            $nameFileAttachment = "{$originalNameAttachment[0]}_{$data}.{$extensionAttachment}";
            $uploadAttachment = $request['attachments.' . $key . '.attachment']->storeAs('attachment', $nameFileAttachment);

            if (!$uploadAttachment) {
                return response('Falha ao realizar o upload do arquivo.', 500)->send();
            }
            return $nameFileAttachment;
        }
    }

    public function getPurchaseOrderValue($id)
    {
        $currentValue = 0;
        foreach ($this->purchaseOrderHasInstallments->where('purchase_order_id', $id)->get() as $key => $purchaseOrderHasInstallments) {
            $currentValue += $purchaseOrderHasInstallments['portion_amount'] - $purchaseOrderHasInstallments['money_discount'];
        }
        return $currentValue;
    }

    public function getListInvoicePurchaseOrder($id)
    {
        $getListPaymentRequestIds = $this->paymentRequestHasPurchaseOrders::join('payment_requests', 'payment_request_has_purchase_orders.payment_request_id', 'payment_requests.id')
            ->select('payment_request_has_purchase_orders.purchase_order_id', 'payment_request_has_purchase_orders.payment_request_id', 'payment_requests.payment_type')
            ->where([
                'payment_request_has_purchase_orders.purchase_order_id' => $id,
                'payment_requests.payment_type' => 0
            ])
            ->get(['payment_request_has_purchase_orders.payment_request_id']);

        $listInvoice = [];
        foreach ($getListPaymentRequestIds as $getListPaymentRequestId) {
            $status = 'n';
            $delivered = 0;
            $total = 0;
            $getPaymentRequest = $this->paymentRequest->withoutGlobalScopes()->where([
                'id' => $getListPaymentRequestId->payment_request_id,
                'payment_type' => 0
            ])->first();
            if ($getPaymentRequest != null) {
                $getPurchaseOrderDeliverys = PurchaseOrderDelivery::where([
                    'payment_request_id' => $getPaymentRequest['id'],
                    'purchase_order_id' => $id
                ])->get();
                if ($getPurchaseOrderDeliverys != null) {
                    foreach ($getPurchaseOrderDeliverys as $getPurchaseOrderDelivery) {
                        if ($getPurchaseOrderDelivery->status == 0) {
                            $status = " "; //new
                        }
                        if ($getPurchaseOrderDelivery->status == 1) {
                            $status = "*"; //pendente
                        }
                        if ($getPurchaseOrderDelivery->status == 2) {
                            $status = "*"; //concluido
                        }
                    }
                }
                $listInvoice[] = [
                    'id' => $getPaymentRequest->id,
                    'invoice_number' => $getPaymentRequest->invoice_number,
                    'emission_date' => $getPaymentRequest->emission_date,
                    'status' => $status
                ];
            }
        }
        return $listInvoice;
    }

    public function getInvoicePurchaseOrder($id)
    {
        $getPaymentRequests = $this->paymentRequest::withoutGlobalScopes()->where('id', $id)->with('purchase_order')->get();
        if (!$getPaymentRequests->isEmpty()) {
            $response = [];
            $products = [];
            $balance_quantity = 0;
            foreach ($getPaymentRequests as $getPaymentRequest) {
                $pay_id = $getPaymentRequest->id;
                foreach ($getPaymentRequest->purchase_order as $getPaymentRequestsPurchaseOrder) {
                    $pur_id = $getPaymentRequestsPurchaseOrder->purchase_order_id;
                    if (PurchaseOrder::where('id', $pur_id)->exists()) {
                        foreach ($getPaymentRequestsPurchaseOrder->purchase_order->products as $getPaymentRequestsPurchaseOrderProduct) {
                            $quantidade_prod = PurchaseOrderDelivery::where([
                                'payment_request_id' =>  $pay_id,
                                'purchase_order_id' =>  $pur_id,
                                'product_id' => $getPaymentRequestsPurchaseOrderProduct->product->id
                            ])->first(['delivery_quantity']);

                            $balances = PurchaseOrderDelivery::where([
                                'purchase_order_id' =>  $pur_id,
                                'product_id' => $getPaymentRequestsPurchaseOrderProduct->product->id
                            ])->get(['delivery_quantity']);

                            if (!$balances->isEmpty()) {

                                foreach ($balances as $balance) {
                                    $balance_quantity += $balance->delivery_quantity;
                                }
                            }

                            $products[] = [
                                'product_id' => $getPaymentRequestsPurchaseOrderProduct->product->id,
                                'product_name' => $getPaymentRequestsPurchaseOrderProduct->product->title,
                                'quantity' => $getPaymentRequestsPurchaseOrderProduct->quantity,
                                'unitary_value' => $getPaymentRequestsPurchaseOrderProduct->unitary_value,
                                'percentage_discount' => $getPaymentRequestsPurchaseOrderProduct->percentage_discount,
                                'money_discount' => $getPaymentRequestsPurchaseOrderProduct->money_discount,
                                'delivery_quantity' => $quantidade_prod['delivery_quantity'] ?? 0,
                                'total_delivery_quantity' => $balance_quantity
                            ];

                            $balance_quantity = 0;
                        }
                    } else {
                        return response()->json([
                            'error' => 'Pedido de compra ' . $pur_id . ' não existe.',
                        ], 422);
                    }
                }
            }
            $response[] = [
                'payment_request_id' => $pay_id,
                'purchase_order_id' => $pur_id,
                'products' => $products
            ];
            return $response;
        } else {
            return $getPaymentRequests;
        }
    }

    public function putPurchaseOrderDelivery($purchaseOrderDeliveryInfo)
    {
        $response = [];
        $totalDeliveryQuantity = 0;
        $totalQuantity = 0;
        $ids = [];
        try {
            if (array_key_exists('products', $purchaseOrderDeliveryInfo)) {
                foreach ($purchaseOrderDeliveryInfo['products'] as $product) {
                    $ids[] = $product['product_id'];
                    $purchaseOrderDeliveryProduct =  PurchaseOrderDelivery::where([
                        'payment_request_id' => $purchaseOrderDeliveryInfo['payment_request_id'],
                        'purchase_order_id' =>  $purchaseOrderDeliveryInfo['purchase_order_id'],
                        'product_id' => $product['product_id']
                    ])->first();

                    if ($purchaseOrderDeliveryProduct != null) {
                        $purchaseOrderDeliveryProduct->update([
                            'delivery_quantity' => $product['delivery_quantity'],
                            'quantity' => $product['quantity']
                        ]);
                        $response[] = $purchaseOrderDeliveryProduct;
                    } else {
                        $purchaseOrderDelivery = PurchaseOrderDelivery::create([
                            'payment_request_id' => $purchaseOrderDeliveryInfo['payment_request_id'],
                            'purchase_order_id' =>  $purchaseOrderDeliveryInfo['purchase_order_id'],
                            'product_id' => $product['product_id'],
                            'delivery_quantity' => $product['delivery_quantity'],
                            'quantity' => $product['quantity']
                        ]);
                        $response[] = $purchaseOrderDelivery;
                    }
                }

                //update status
                $totalDeliveryQuantity = PurchaseOrderDelivery::where(
                    'purchase_order_id',
                    $purchaseOrderDeliveryInfo['purchase_order_id']
                )
                    ->whereIn('product_id', $ids)
                    ->sum('delivery_quantity');

                if ($totalDeliveryQuantity != null) {
                    $totalQuantity =  PurchaseOrderHasProducts::where(
                        'purchase_order_id',
                        $purchaseOrderDeliveryInfo['purchase_order_id']
                    )->whereIn('product_id', $ids)->sum('quantity');
                    PurchaseOrderDelivery::where([
                        'purchase_order_id' => $purchaseOrderDeliveryInfo['purchase_order_id'],
                        'payment_request_id' => $purchaseOrderDeliveryInfo['payment_request_id']
                    ])
                        ->whereIn('product_id', $ids)
                        ->update(
                            [
                                'status' => ($totalQuantity - $totalDeliveryQuantity) == 0 ? 2 : 1,
                            ]
                        );
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao salvar os dados no banco de dados'
            ], 500);
        }

        return $response;
    }

    public function getPurchaseOrderStatus($purchaseOrderInfo)
    {
        $response = 1;
        // validar Servicos e produtos
        $quotationItemsIds = ProviderQuotationItems::where('provider_quotation_id', $purchaseOrderInfo['quotation_id'])->get(['id']);
        $countServices = 0;
        $countProducts = 0;
        $SumProducts = 0;
        foreach ($quotationItemsIds as $quotationItemsId) {
            $countServices = ProviderQuotationHasServices::where('provider_quotation_item_id', $quotationItemsId->id)->count();
            $countProducts = ProviderQuotationHasProducts::where('provider_quotation_item_id', $quotationItemsId->id)->count();
            $SumProducts = ProviderQuotationHasProducts::where('provider_quotation_item_id', $quotationItemsId->id)->sum('quantity');
        }

        $purchaseOrderIds = PurchaseOrder::where('quotation_id', $purchaseOrderInfo['quotation_id'])->get(['id']);
        $totalServices = 0;
        $totalListProducts = [];
        $totalProducts = 0;
        $totalSumProducts = 0;
        foreach ($purchaseOrderIds as $purchaseOrderId) {
            $totalServices += PurchaseOrderHasServices::where('purchase_order_id', $purchaseOrderId->id)->count();
            $totalProducts = PurchaseOrderHasProducts::select('product_id', \DB::raw('count(*) as total'))->where('purchase_order_id', $purchaseOrderId->id)->groupBy('product_id')->get();
            foreach ($totalProducts as $totalProduct) {
                if (!in_array($totalProduct->product_id, $totalListProducts)) {
                    $totalListProducts[] = $totalProduct->product_id;
                }
            }
            $totalSumProducts += PurchaseOrderHasProducts::where('purchase_order_id', $purchaseOrderId->id)->sum('quantity');
        }
        //validacao final
        if (($countServices == $totalServices) && ($countProducts == count($totalListProducts)) && ($SumProducts == $totalSumProducts)) {
            $response = 2;
        }

        return $response;
    }
}
