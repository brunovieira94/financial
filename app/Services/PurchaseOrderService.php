<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderHasProducts;
use App\Models\PurchaseOrderHasServices;
use App\Models\PurchaseOrderHasCostCenters;
use App\Models\PurchaseOrderHasAttachments;
use App\Models\PurchaseOrderServicesHasInstallments;
use App\Models\SupplyApprovalFlow;

use Illuminate\Http\Request;

class PurchaseOrderService
{
    private $purchaseOrder;
    private $purchaseOrderHasProducts;
    private $purchaseOrderHasServices;
    private $purchaseOrderHasCostCenters;
    private $purchaseOrderServicesHasInstallments;
    private $attachments;

    private $with = ['approval','cost_centers', 'attachments', 'services', 'products', 'currency', 'provider'];

    public function __construct(PurchaseOrder $purchaseOrder, PurchaseOrderHasProducts $purchaseOrderHasProducts, PurchaseOrderHasServices $purchaseOrderHasServices, PurchaseOrderHasCostCenters $purchaseOrderHasCostCenters, PurchaseOrderHasAttachments $attachments, PurchaseOrderServicesHasInstallments $purchaseOrderServicesHasInstallments)
    {
        $this->purchaseOrder = $purchaseOrder;
        $this->purchaseOrderHasProducts = $purchaseOrderHasProducts;
        $this->purchaseOrderHasServices = $purchaseOrderHasServices;
        $this->purchaseOrderHasCostCenters = $purchaseOrderHasCostCenters;
        $this->attachments = $attachments;
        $this->purchaseOrderServicesHasInstallments = $purchaseOrderServicesHasInstallments;
    }

    public function getAllPurchaseOrder($requestInfo)
    {
        $purchaseOrder = Utils::search($this->purchaseOrder, $requestInfo);
        return Utils::pagination($purchaseOrder->with($this->with), $requestInfo);
    }

    public function getPurchaseOrder($id)
    {
        return $this->purchaseOrder->with($this->with)->findOrFail($id);
    }

    public function postPurchaseOrder($purchaseOrderInfo, Request $request)
    {
        $purchaseOrder = new PurchaseOrder;
        $purchaseOrder = $purchaseOrder->create($purchaseOrderInfo);
        $this->syncProducts($purchaseOrder, $purchaseOrderInfo);
        $this->syncServices($purchaseOrder, $purchaseOrderInfo);
        $this->syncCostCenters($purchaseOrder, $purchaseOrderInfo);
        $this->syncAttachments($purchaseOrder, $purchaseOrderInfo, $request);

        $supplyApprovalFlow = new SupplyApprovalFlow;
        $supplyApprovalFlow = $supplyApprovalFlow->create([
            'id_purchase_order' => $purchaseOrder->id,
            'order' => 0,
            'status' => 0,
        ]);
        return $this->purchaseOrder->with($this->with)->findOrFail($purchaseOrder->id);
    }

    public function putPurchaseOrder($id, $purchaseOrderInfo, Request $request)
    {
        $purchaseOrder = $this->purchaseOrder->findOrFail($id);
        if(!array_key_exists('payment_condition', $purchaseOrderInfo)) {
            $purchaseOrderInfo['payment_condition'] = null;
        }
        if(!array_key_exists('billing_date', $purchaseOrderInfo)) {
            $purchaseOrderInfo['billing_date'] = null;
        }

        $oldValue = 0;
        $newValue = 0;
        foreach ($this->purchaseOrderHasServices->with('installments')->where('purchase_order_id', $id)->get() as $key => $purchaseOrderHasServices) {
            $serviceValue = 0;
            $serviceDiscount = 0;
            foreach ($purchaseOrderHasServices['installments'] as $value) {
                $serviceValue += $value['portion_amount'];
                $serviceDiscount += $value['money_discount'];
            }
            if($purchaseOrder->services[$key]['unique_discount'] == 1){
                $serviceDiscount = $purchaseOrder->services[$key]['money_discount'];
            }
            $serviceValue -= $serviceDiscount;
            $oldValue += $serviceValue;
        }
        foreach ($this->purchaseOrderHasProducts->where('purchase_order_id', $id)->get() as $value) {
            $productValue = $value['unitary_value']*$value['quantity'];
            $productDiscount = 0;
            if($purchaseOrder['unique_product_discount'] == 0){
                $productDiscount = $value['money_discount'];
            }
            $productValue -= $productDiscount;
            $oldValue += $productValue;
        }
        if($purchaseOrder['unique_product_discount']){
            $oldValue -= $purchaseOrder['money_discount_products'];
        }

        $purchaseOrder->fill($purchaseOrderInfo)->save();
        $this->putProducts($id, $purchaseOrderInfo);
        $this->putServices($id, $purchaseOrderInfo);

        foreach ($this->purchaseOrderHasServices->with('installments')->where('purchase_order_id', $id)->get() as $key => $purchaseOrderHasServices) {
            $serviceValue = 0;
            $serviceDiscount = 0;
            foreach ($purchaseOrderHasServices['installments'] as $value) {
                $serviceValue += $value['portion_amount'];
                $serviceDiscount += $value['money_discount'];
            }
            if($purchaseOrder->services[$key]['unique_discount'] == 1){
                $serviceDiscount = $purchaseOrder->services[$key]['money_discount'];
            }
            $serviceValue -= $serviceDiscount;
            $newValue += $serviceValue;
        }
        foreach ($this->purchaseOrderHasProducts->where('purchase_order_id', $id)->get() as $value) {
            $productValue = $value['unitary_value']*$value['quantity'];
            $productDiscount = 0;
            if($purchaseOrder['unique_product_discount'] == 0){
                $productDiscount = $value['money_discount'];
            }
            $productValue -= $productDiscount;
            $newValue += $productValue;
        }
        if($purchaseOrder['unique_product_discount']){
            $newValue -= $purchaseOrder['money_discount_products'];
        }


        if($newValue > ($oldValue + ($oldValue*$purchaseOrder['increase_tolerance']/100))){
            $supplyApprovalFlow = SupplyApprovalFlow::find($purchaseOrder->approval['id']);
            $supplyApprovalFlow['order'] = 0;
            $supplyApprovalFlow->save();
        }

        $this->putCostCenters($id, $purchaseOrderInfo);
        $this->putAttachments($id, $purchaseOrderInfo, $request);
        return $this->purchaseOrder->with($this->with)->findOrFail($purchaseOrder->id);
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
                    'automatic_renovation' => $service['automatic_renovation'],
                    'notice_time_to_renew' => $service['notice_time_to_renew'],
                    'percentage_discount' => $service['percentage_discount'],
                    'money_discount' => $service['money_discount'],
                    'frequency_of_installments' => $service['frequency_of_installments'],
                    'contract_duration' => $service['contract_duration'],
                    'unique_discount' => $service['unique_discount'],
                ]);
                $this->syncInstallments($purchaseOrderHasServices, $service);
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
                        'automatic_renovation' => $service['automatic_renovation'],
                        'notice_time_to_renew' => $service['notice_time_to_renew'],
                        'percentage_discount' => $service['percentage_discount'],
                        'money_discount' => $service['money_discount'],
                        'frequency_of_installments' => $service['frequency_of_installments'],
                        'contract_duration' => $service['contract_duration'],
                        'unique_discount' => $service['unique_discount'],
                    ]);
                    $createdServices[] = $purchaseOrderHasServices->id;
                }
                $this->syncInstallments($purchaseOrderHasServices, $service);
            }
        }

        $collection = $this->purchaseOrderHasServices->where('purchase_order_id', $id)->whereNotIn('id', $updateServices)->whereNotIn('id', $createdServices)->get(['id']);
        $this->purchaseOrderHasServices->destroy($collection->toArray());
    }

    public function destroyInstallments($purchaseOrderHasServices)
    {
        $collection = $this->purchaseOrderServicesHasInstallments->where('po_services_id', $purchaseOrderHasServices['id'])->get(['id']);
        $this->purchaseOrderServicesHasInstallments->destroy($collection->toArray());
    }

    public function syncInstallments($purchaseOrderHasServices, $service)
    {
        if (array_key_exists('installments', $service)) {
            $this->destroyInstallments($purchaseOrderHasServices);
            foreach ($service['installments'] as $key => $installments) {
                $purchaseOrderServicesHasInstallments = new PurchaseOrderServicesHasInstallments;
                $installments['po_services_id'] = $purchaseOrderHasServices['id'];
                $installments['parcel_number'] = $key + 1;
                try {
                    $purchaseOrderServicesHasInstallments = $purchaseOrderServicesHasInstallments->create($installments);
                } catch (\Exception $e) {
                    $this->destroyInstallments($purchaseOrderHasServices);
                    $this->purchaseOrderHasServices->findOrFail($purchaseOrderHasServices->id)->delete();
                    return response('Falha ao salvar as parcelas no banco de dados.', 500)->send();
                }
            }
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
}
