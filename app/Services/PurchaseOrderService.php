<?php

namespace App\Services;

use App\Models\Module;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestHasPurchaseOrders;
use App\Models\PurchaseOrder;
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

    private $with = ['user', 'installments', 'approval', 'cost_centers', 'attachments', 'services', 'products', 'company', 'currency', 'provider', 'purchase_requests'];

    public function __construct(PurchaseOrder $purchaseOrder, PurchaseRequest $purchaseRequest, PurchaseRequestHasProducts $purchaseRequestHasProducts, PurchaseOrderHasProducts $purchaseOrderHasProducts, PurchaseOrderHasCompanies $purchaseOrderHasCompanies, PurchaseOrderHasServices $purchaseOrderHasServices, PurchaseOrderHasCostCenters $purchaseOrderHasCostCenters, PurchaseOrderHasAttachments $attachments, PurchaseOrderServicesHasInstallments $purchaseOrderServicesHasInstallments, PurchaseOrderHasPurchaseRequests $purchaseOrderHasPurchaseRequests, PurchaseOrderHasInstallments $purchaseOrderHasInstallments, PaymentRequestHasPurchaseOrders $paymentRequestHasPurchaseOrders, PaymentRequest $paymentRequest)
    {
        $this->purchaseOrder = $purchaseOrder;
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

        if (array_key_exists('provider', $requestInfo)) {
            $purchaseOrder->whereHas('provider', function ($query) use ($requestInfo) {
                $query->where('provider_id', $requestInfo['provider']);
            });
        }

        if (array_key_exists('cost_center', $requestInfo)) {
            $purchaseOrder->whereHas('cost_centers', function ($query) use ($requestInfo) {
                $query->where('cost_center_id', $requestInfo['cost_center']);
            });
        }

        if (array_key_exists('service', $requestInfo)) {
            $purchaseOrder->whereHas('services', function ($query) use ($requestInfo) {
                $query->where('service_id', $requestInfo['service']);
            });
        }

        if (array_key_exists('product', $requestInfo)) {
            $purchaseOrder->whereHas('products', function ($query) use ($requestInfo) {
                $query->where('product_id', $requestInfo['product']);
            });
        }

        if (array_key_exists('billing_date', $requestInfo)) {
            if (array_key_exists('from', $requestInfo['billing_date'])) {
                $purchaseOrder->where('billing_date', '>=', $requestInfo['billing_date']['from']);
            }
            if (array_key_exists('to', $requestInfo['billing_date'])) {
                $purchaseOrder->where('billing_date', '<=', $requestInfo['billing_date']['to']);
            }
        }

        return Utils::pagination($purchaseOrder->with($this->with), $requestInfo);
    }

    public function getPurchaseOrder($id)
    {
        return $this->purchaseOrder->with($this->with)->findOrFail($id);
    }

    public function postPurchaseOrder($purchaseOrderInfo, Request $request)
    {
        $purchaseOrder = new PurchaseOrder;
        $purchaseOrderInfo['user_id'] = auth()->user()->id;
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

            $oldValue = $this->getPurchaseOrderValue($purchaseOrder, $id);

            $purchaseOrder->fill($purchaseOrderInfo)->save();
            $this->putProducts($id, $purchaseOrderInfo);
            $this->putServices($id, $purchaseOrderInfo);

            $newValue = $this->getPurchaseOrderValue($purchaseOrder, $id);

            // caso o valor for maior do que o antigo o pedido deve voltar para o início da aprovação
            if ($newValue > ($oldValue + ($oldValue * $purchaseOrder['increase_tolerance'] / 100))) {
                $supplyApprovalFlow = SupplyApprovalFlow::find($purchaseOrder->approval['id']);
                $supplyApprovalFlow['order'] = 0;
                if ($newStatus != '') {
                    $supplyApprovalFlow['status'] = $newStatus;
                }
                $supplyApprovalFlow->save();
            }

            $this->putCompanies($id, $purchaseOrderInfo);
            $this->putCostCenters($id, $purchaseOrderInfo);
            $this->putAttachments($id, $purchaseOrderInfo, $request);

            $discount = 0;
            $new_final_negotiated_total_value = 0;
            foreach ($purchaseOrderInfo['installments'] as $key => $installments) {
                $discount += $installments['discount'];
            }
            if ($discount > 0) {
                $new_final_negotiated_total_value = $purchaseOrderInfo['final_negotiated_total_value'] + $discount;
            } else {
                $new_final_negotiated_total_value = $purchaseOrderInfo['final_negotiated_total_value'];
            }

            if ($purchaseOrderInfo['negotiated_total_value'] == $new_final_negotiated_total_value) {
                $this->syncInstallments($purchaseOrder, $purchaseOrderInfo);
            } else {
                return response()->json([
                    'error' => 'O valor total inicial e o valor total das parcelas não são iguais. Por favor validar o valor das parcelas.',
                ], 422);
            }

            return $this->purchaseOrder->with($this->with)->findOrFail($purchaseOrder->id);
        } else {
            return response()->json([
                'error' => 'Não é permitido ao usuário editar o pedido ' . $id,
            ], 422);
        }
    }

    public function deletePurchaseOrder($id)
    {
        $this->purchaseOrder->findOrFail($id)->delete();
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
        }

        $collection = $this->purchaseOrderHasProducts->where('purchase_order_id', $id)->whereNotIn('id', $updateProducts)->whereNotIn('id', $createdProducts)->get(['id']);
        $this->purchaseOrderHasProducts->destroy($collection->toArray());
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
        }

        $collection = $this->purchaseOrderHasServices->where('purchase_order_id', $id)->whereNotIn('id', $updateServices)->whereNotIn('id', $createdServices)->get(['id']);
        $this->purchaseOrderHasServices->destroy($collection->makeHidden(['end_contract_date'])->toArray());
    }

    // public function destroyInstallments($purchaseOrderHasServices)
    // {
    //     $collection = $this->purchaseOrderServicesHasInstallments->where('po_services_id', $purchaseOrderHasServices['id'])->get(['id']);
    //     $this->purchaseOrderServicesHasInstallments->destroy($collection->toArray());
    // }

    // public function syncInstallments($purchaseOrderHasServices, $service)
    // {
    //     if (array_key_exists('installments', $service)) {
    //         $this->destroyInstallments($purchaseOrderHasServices);
    //         foreach ($service['installments'] as $key => $installments) {
    //             $purchaseOrderServicesHasInstallments = new PurchaseOrderServicesHasInstallments;
    //             $installments['po_services_id'] = $purchaseOrderHasServices['id'];
    //             $installments['parcel_number'] = $key + 1;
    //             try {
    //                 $purchaseOrderServicesHasInstallments = $purchaseOrderServicesHasInstallments->create($installments);
    //             } catch (\Exception $e) {
    //                 $this->destroyInstallments($purchaseOrderHasServices);
    //                 $this->purchaseOrderHasServices->findOrFail($purchaseOrderHasServices->id)->delete();
    //                 return response('Falha ao salvar as parcelas no banco de dados.', 500)->send();
    //             }
    //         }
    //     }
    // }

    public function syncInstallments($purchaseOrder, $purchaseOrderInfo)
    {
        if (array_key_exists('installments', $purchaseOrderInfo)) {
            $this->destroyInstallments($purchaseOrder);
            foreach ($purchaseOrderInfo['installments'] as $key => $installments) {
                $purchaseOrderHasInstallments = new PurchaseOrderHasInstallments;
                $installments['purchase_order_id'] = $purchaseOrder['id'];
                $installments['parcel_number'] = $key + 1;
                try {
                    $purchaseOrderHasInstallments = $purchaseOrderHasInstallments->create($installments);
                } catch (\Exception $e) {
                    $this->destroyInstallments($purchaseOrder);
                    $this->purchaseOrder->findOrFail($purchaseOrder->id)->delete();
                    return response('Falha ao salvar as parcelas no banco de dados.', 500)->send();
                }
            }
        }
    }

    public function destroyInstallments($purchaseOrder)
    {
        $collection = $this->purchaseOrderHasInstallments->where('purchase_order_id', $purchaseOrder['id'])->get(['id']);
        $this->purchaseOrderHasInstallments->destroy($collection->toArray());
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


    // public function isPartialRequest($purchaseOrderInfo, $purchaseRequest)
    // {
    //     foreach ($this->purchaseRequestHasProducts->where('purchase_request_id', $purchaseRequest['purchase_request_id'])->get() as $purchaseRequestHasProducts) {
    //         foreach ($purchaseOrderInfo['products'] as $product) {
    //             if($product['product_id'] == $purchaseRequestHasProducts['product_id'] && $product['quantity'] != $purchaseRequestHasProducts['quantity'])
    //             {
    //                 $totalQuantity = 0;
    //                 // criar um array de quantidade ja alocada que o index é o id do produto, subtrair dele
    //                 $purchaseOrderIdArray = $this->purchaseOrderHasPurchaseRequests->where('purchase_request_id', $purchaseRequest['purchase_request_id'])->pluck('purchase_order_id')->toArray();
    //                 foreach ($purchaseOrderIdArray as $purchaseOrderId) {
    //                     foreach ($this->purchaseOrderHasProducts->where('purchase_order_id', $purchaseOrderId)->where('product_id', $product['product_id'])->get() as $productFound) {
    //                         $totalQuantity += $productFound['quantity'];
    //                     }
    //                 }
    //                 if($totalQuantity < $purchaseRequestHasProducts['quantity']){
    //                     return true;
    //                 }
    //             }
    //         }
    //     }
    //     return false;
    // }

    // public function putPurchaseRequests($id, $purchaseOrderInfo)
    // {

    //     $updatePurchaseRequests = [];
    //     $createdPurchaseRequests = [];

    //     if (array_key_exists('purchase_requests', $purchaseOrderInfo)) {
    //         foreach ($purchaseOrderInfo['purchase_requests'] as $purchaseRequest) {
    //             if (array_key_exists('id', $purchaseRequest)) {
    //                 $purchaseOrderHasPurchaseRequests = $this->purchaseOrderHasPurchaseRequests->findOrFail($purchaseRequest['id']);
    //                 $purchaseOrderHasPurchaseRequests->fill($purchaseRequest)->save();
    //                 $updatePurchaseRequests[] = $purchaseRequest['id'];
    //             } else {
    //                 $purchaseOrderHasPurchaseRequests = $this->purchaseOrderHasPurchaseRequests->create([
    //                     'purchase_order_id' => $id,
    //                     'purchase_request_id' => $purchaseRequest['purchase_request_id'],
    //                 ]);
    //                 $createdPurchaseRequests[] = $purchaseOrderHasPurchaseRequests->id;
    //             }
    //         }
    //     }

    //     $collection = $this->purchaseOrderHasPurchaseRequests->where('purchase_order_id', $id)->whereNotIn('id', $updatePurchaseRequests)->whereNotIn('id', $createdPurchaseRequests)->get(['id']);
    //     $this->purchaseOrderHasPurchaseRequests->destroy($collection->toArray());
    // }

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
            $updateAttachments[] = $purchaseOrderInfo['attachments_ids'];
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

    public function getPurchaseOrderValue($purchaseOrder, $id)
    {
        $currentValue = 0;
        foreach ($this->purchaseOrderHasServices->with('installments')->where('purchase_order_id', $id)->get() as $key => $purchaseOrderHasServices) {
            $serviceValue = 0;
            $serviceDiscount = 0;
            foreach ($purchaseOrderHasServices['installments'] as $value) {
                $serviceValue += $value['portion_amount'];
                $serviceDiscount += $value['money_discount'];
            }
            if ($purchaseOrder->services[$key]['unique_discount'] == 1) {
                $serviceDiscount = $purchaseOrder->services[$key]['money_discount'];
            }
            $serviceValue -= $serviceDiscount;
            $currentValue += $serviceValue;
        }
        foreach ($this->purchaseOrderHasProducts->where('purchase_order_id', $id)->get() as $value) {
            $productValue = $value['unitary_value'] * $value['quantity'];
            $productDiscount = 0;
            if ($purchaseOrder['unique_product_discount'] == 0) {
                $productDiscount = $value['money_discount'];
            }
            $productValue -= $productDiscount;
            $currentValue += $productValue;
        }
        if ($purchaseOrder['unique_product_discount']) {
            $currentValue -= $purchaseOrder['money_discount_products'];
        }
        return $currentValue;
    }

    public function getListInvoicePurchaseOrder($id)
    {
        $getListPaymentRequestIds = $this->paymentRequestHasPurchaseOrders::where('purchase_order_id', $id)->get(['payment_request_id']);

        $listInvoice = [];
        foreach ($getListPaymentRequestIds as $getListPaymentRequestId) {
            $getPaymentRequest = $this->paymentRequest->where([
                'id' => $getListPaymentRequestId->payment_request_id,
                'payment_type' => 0
            ])->first();
            if ($getPaymentRequest != null) {
                $listInvoice[] = [
                    'id' => $getPaymentRequest->id,
                    'emission_date' => $getPaymentRequest->emission_date
                ];
            }
        }
        return $listInvoice;
    }

    public function getInvoicePurchaseOrder($id)
    {
        $gePaymentRequest = $this->paymentRequest::where('id', $id)->with('purchase_order')->get();

        return $gePaymentRequest;
    }
}
