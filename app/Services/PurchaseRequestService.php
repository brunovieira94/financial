<?php

namespace App\Services;

use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestHasProducts;
use App\Models\PurchaseRequestHasCompanies;
use App\Models\PurchaseRequestHasServices;
use App\Models\PurchaseRequestHasCostCenters;
use App\Models\PurchaseRequestHasAttachments;
use App\Models\SupplyApprovalFlow;

use Illuminate\Http\Request;

class PurchaseRequestService
{
    private $purchaseRequest;
    private $purchaseRequestHasProducts;
    private $purchaseRequestHasCompanies;
    private $purchaseRequestHasServices;
    private $purchaseRequestHasCostCenters;
    private $attachments;

    private $with = ['cost_centers', 'attachments', 'services', 'products', 'companies', 'provider'];

    public function __construct(PurchaseRequest $purchaseRequest, PurchaseRequestHasProducts $purchaseRequestHasProducts, PurchaseRequestHasCompanies $purchaseRequestHasCompanies, PurchaseRequestHasServices $purchaseRequestHasServices, PurchaseRequestHasCostCenters $purchaseRequestHasCostCenters, PurchaseRequestHasAttachments $attachments)
    {
        $this->purchaseRequest = $purchaseRequest;
        $this->purchaseRequestHasProducts = $purchaseRequestHasProducts;
        $this->purchaseRequestHasCompanies = $purchaseRequestHasCompanies;
        $this->purchaseRequestHasServices = $purchaseRequestHasServices;
        $this->purchaseRequestHasCostCenters = $purchaseRequestHasCostCenters;
        $this->attachments = $attachments;
    }

    public function getAllPurchaseRequest($requestInfo)
    {
        $purchaseRequest = Utils::search($this->purchaseRequest, $requestInfo);
        return Utils::pagination($purchaseRequest->with($this->with), $requestInfo);
    }

    public function getPurchaseRequest($id)
    {
        return $this->purchaseRequest->with($this->with)->findOrFail($id);
    }

    public function postPurchaseRequest($purchaseRequestInfo, Request $request)
    {
        $purchaseRequest = new PurchaseRequest;
        $purchaseRequest = $purchaseRequest->create($purchaseRequestInfo);
        $this->syncProducts($purchaseRequest, $purchaseRequestInfo);
        $this->syncServices($purchaseRequest, $purchaseRequestInfo);
        $this->syncCompanies($purchaseRequest, $purchaseRequestInfo);
        $this->syncCostCenters($purchaseRequest, $purchaseRequestInfo);
        $this->syncAttachments($purchaseRequest, $purchaseRequestInfo, $request);
        return $this->purchaseRequest->with($this->with)->findOrFail($purchaseRequest->id);
    }

    public function putPurchaseRequest($id, $purchaseRequestInfo, Request $request)
    {
        $purchaseRequest = $this->purchaseRequest->findOrFail($id);
        $purchaseRequest->fill($purchaseRequestInfo)->save();
        $this->putProducts($id, $purchaseRequestInfo);
        $this->putServices($id, $purchaseRequestInfo);
        $this->putCompanies($id, $purchaseRequestInfo);
        $this->putCostCenters($id, $purchaseRequestInfo);
        $this->putAttachments($id, $purchaseRequestInfo, $request);
        return $this->purchaseRequest->with($this->with)->findOrFail($purchaseRequest->id);
    }

    public function deletePurchaseRequest($id)
    {
        $this->purchaseRequest->findOrFail($id)->delete();
        return true;
    }

    public function syncProducts($purchaseRequest, $purchaseRequestInfo)
    {
        if (array_key_exists('products', $purchaseRequestInfo)) {
            foreach ($purchaseRequestInfo['products'] as $product) {
                $purchaseRequestHasProducts = new PurchaseRequestHasProducts;
                $purchaseRequestHasProducts = $purchaseRequestHasProducts->create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'product_id' => $product['product_id'],
                    'quantity' => $product['quantity'],
                ]);
            }
        }
    }

    public function putProducts($id, $purchaseRequestInfo)
    {

        $updateProducts = [];
        $createdProducts = [];

        if (array_key_exists('products', $purchaseRequestInfo)) {
            foreach ($purchaseRequestInfo['products'] as $product) {
                if (array_key_exists('id', $product)) {
                    $purchaseRequestHasProducts = $this->purchaseRequestHasProducts->findOrFail($product['id']);
                    $purchaseRequestHasProducts->fill($product)->save();
                    $updateProducts[] = $product['id'];
                } else {
                    $purchaseRequestHasProducts = $this->purchaseRequestHasProducts->create([
                        'purchase_request_id' => $id,
                        'product_id' => $product['product_id'],
                        'quantity' => $product['quantity'],
                    ]);
                    $createdProducts[] = $purchaseRequestHasProducts->id;
                }
            }
        }

        $collection = $this->purchaseRequestHasProducts->where('purchase_request_id', $id)->whereNotIn('id', $updateProducts)->whereNotIn('id', $createdProducts)->get(['id']);
        $this->purchaseRequestHasProducts->destroy($collection->toArray());
    }

    public function syncServices($purchaseRequest, $purchaseRequestInfo)
    {
        if (array_key_exists('services', $purchaseRequestInfo)) {
            foreach ($purchaseRequestInfo['services'] as $service) {
                $purchaseRequestHasServices = new PurchaseRequestHasServices;
                $purchaseRequestHasServices = $purchaseRequestHasServices->create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'service_id' => $service['service_id'],
                    'contract_duration' => $service['contract_duration'],
                ]);
            }
        }
    }

    public function putServices($id, $purchaseRequestInfo)
    {

        $updateServices = [];
        $createdServices = [];

        if (array_key_exists('services', $purchaseRequestInfo)) {
            foreach ($purchaseRequestInfo['services'] as $service) {
                if (array_key_exists('id', $service)) {
                    $purchaseRequestHasServices = $this->purchaseRequestHasServices->findOrFail($service['id']);
                    $purchaseRequestHasServices->fill($service)->save();
                    $updateServices[] = $service['id'];
                } else {
                    $purchaseRequestHasServices = $this->purchaseRequestHasServices->create([
                        'purchase_request_id' => $id,
                        'service_id' => $service['service_id'],
                        'contract_duration' => $service['contract_duration'],
                    ]);
                    $createdServices[] = $purchaseRequestHasServices->id;
                }
            }
        }

        $collection = $this->purchaseRequestHasServices->where('purchase_request_id', $id)->whereNotIn('id', $updateServices)->whereNotIn('id', $createdServices)->get(['id']);
        $this->purchaseRequestHasServices->destroy($collection->toArray());
    }

    public function syncCostCenters($purchaseRequest, $purchaseRequestInfo)
    {
        if (array_key_exists('cost_centers', $purchaseRequestInfo)) {
            foreach ($purchaseRequestInfo['cost_centers'] as $costCenter) {
                $purchaseRequestHasCostCenters = new PurchaseRequestHasCostCenters;
                $purchaseRequestHasCostCenters = $purchaseRequestHasCostCenters->create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'cost_center_id' => $costCenter['cost_center_id'],
                    'percentage' => $costCenter['percentage'],
                ]);
            }
        }
    }

    public function putCostCenters($id, $purchaseRequestInfo)
    {

        $updateCostCenters = [];
        $createdCostCenters = [];

        if (array_key_exists('cost_centers', $purchaseRequestInfo)) {
            foreach ($purchaseRequestInfo['cost_centers'] as $costCenter) {
                if (array_key_exists('id', $costCenter)) {
                    $purchaseRequestHasCostCenters = $this->purchaseRequestHasCostCenters->findOrFail($costCenter['id']);
                    $purchaseRequestHasCostCenters->fill($costCenter)->save();
                    $updateCostCenters[] = $costCenter['id'];
                } else {
                    $purchaseRequestHasCostCenters = $this->purchaseRequestHasCostCenters->create([
                        'purchase_request_id' => $id,
                        'cost_center_id' => $costCenter['cost_center_id'],
                        'percentage' => $costCenter['percentage'],
                    ]);
                    $createdCostCenters[] = $purchaseRequestHasCostCenters->id;
                }
            }
        }

        $collection = $this->purchaseRequestHasCostCenters->where('purchase_request_id', $id)->whereNotIn('id', $updateCostCenters)->whereNotIn('id', $createdCostCenters)->get(['id']);
        $this->purchaseRequestHasCostCenters->destroy($collection->toArray());
    }

    public function syncCompanies($purchaseRequest, $purchaseRequestInfo)
    {
        if (array_key_exists('companies', $purchaseRequestInfo)) {
            foreach ($purchaseRequestInfo['companies'] as $company) {
                $purchaseRequestHasCompanies = new PurchaseRequestHasCompanies;
                $purchaseRequestHasCompanies = $purchaseRequestHasCompanies->create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'company_id' => $company['company_id'],
                ]);
            }
        }
    }

    public function putCompanies($id, $purchaseRequestInfo)
    {

        $updateCompanies = [];
        $createdCompanies = [];

        if (array_key_exists('companies', $purchaseRequestInfo)) {
            foreach ($purchaseRequestInfo['companies'] as $company) {
                if (array_key_exists('id', $company)) {
                    $purchaseRequestHasCompanies = $this->purchaseRequestHasCompanies->findOrFail($company['id']);
                    $purchaseRequestHasCompanies->fill($company)->save();
                    $updateCompanies[] = $company['id'];
                } else {
                    $purchaseRequestHasCompanies = $this->purchaseRequestHasCompanies->create([
                        'purchase_request_id' => $id,
                        'company_id' => $company['company_id'],
                    ]);
                    $createdCompanies[] = $purchaseRequestHasCompanies->id;
                }
            }
        }

        $collection = $this->purchaseRequestHasCompanies->where('purchase_request_id', $id)->whereNotIn('id', $updateCompanies)->whereNotIn('id', $createdCompanies)->get(['id']);
        $this->purchaseRequestHasCompanies->destroy($collection->toArray());
    }

    public function syncAttachments($purchaseRequest, $purchaseRequestInfo, Request $request)
    {
        if (array_key_exists('attachments', $purchaseRequestInfo)) {
            foreach ($purchaseRequestInfo['attachments'] as $key => $attachment) {
                $purchaseRequestHasAttachments = new PurchaseRequestHasAttachments;
                $attachment['attachment'] = $this->storeAttachment($request, $key);
                $purchaseRequestHasAttachments = $purchaseRequestHasAttachments->create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'attachment' => $attachment['attachment'],
                ]);
            }
        }
    }

    public function putAttachments($id, $purchaseRequestInfo, Request $request)
    {

        $updateAttachments = [];
        $createdAttachments = [];
        $destroyCollection = [];

        if (array_key_exists('attachments_ids', $purchaseRequestInfo)) {
            $updateAttachments[] = $purchaseRequestInfo['attachments_ids'];
        }
        if (array_key_exists('attachments', $purchaseRequestInfo)) {
            foreach ($purchaseRequestInfo['attachments'] as $key => $attachment) {
                $purchaseRequestHasAttachments = new PurchaseRequestHasAttachments;
                $attachment['attachment'] = $this->storeAttachment($request, $key);
                $purchaseRequestHasAttachments = $purchaseRequestHasAttachments->create([
                    'purchase_request_id' => $id,
                    'attachment' => $attachment['attachment'],
                ]);
                $createdAttachments[] = $purchaseRequestHasAttachments->id;
            }
        }

        $collection = $this->attachments->where('purchase_request_id', $id)->whereNotIn('id', $updateAttachments)->whereNotIn('id', $createdAttachments)->get();
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
