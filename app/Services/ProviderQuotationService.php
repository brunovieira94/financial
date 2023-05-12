<?php

namespace App\Services;

use App\Models\Module;
use App\Models\ProviderQuotation;
use App\Models\ProviderQuotationHasCostCenters;
use App\Models\ProviderQuotationHasProducts;
use App\Models\ProviderQuotationHasServices;
use App\Models\ProviderQuotationItems;
use App\Models\PurchaseRequest;
use App\Models\RoleHasModule;
use Illuminate\Http\Request;
use phpDocumentor\Reflection\Types\Nullable;

class ProviderQuotationService
{
    private $providerQuotation;
    private $providerQuotationHasProducts;
    private $providerQuotationHasServices;
    private $providerQuotationItems;

    private $with = ['quotation_items', 'cost_centers', 'company'];

    public function __construct(ProviderQuotation $providerQuotation, ProviderQuotationHasProducts $providerQuotationHasProducts, ProviderQuotationHasServices $providerQuotationHasServices, ProviderQuotationItems $providerQuotationItems)
    {
        $this->providerQuotation = $providerQuotation;
        $this->providerQuotationHasProducts = $providerQuotationHasProducts;
        $this->providerQuotationHasServices = $providerQuotationHasServices;
        $this->providerQuotationItems = $providerQuotationItems;
    }

    public function getAllProviderQuotation($requestInfo)
    {
        $providerQuotation = Utils::search($this->providerQuotation, $requestInfo);
        //filter cost center
        if (auth()->user()->role->filter_cost_center_supply) {
            $providerQuotation->whereHas('cost_centers', function ($query) {
                $query->whereIn('cost_center_id', auth()->user()->cost_center->pluck('id')->toArray() ?? []);
            });
        }
        return Utils::pagination($providerQuotation->with($this->with), $requestInfo);
    }

    public function getProviderQuotation($id)
    {
        return $this->providerQuotation->with($this->with)->findOrFail($id);
    }

    public function postProviderQuotation($providerQuotationInfo, Request $request)
    {
        if ($providerQuotationInfo != null) {
            $providerQuotation = new ProviderQuotation;
            $providerQuotationInfo['user_id'] = auth()->user()->id;
            $providerQuotation = $providerQuotation->create(
                [
                    'user_id' => $providerQuotationInfo['user_id'],
                    'request_ids' => $providerQuotationInfo['request_ids'],
                    'services_list_export' => $providerQuotationInfo['services_list_export'],
                    'request_type' => $providerQuotationInfo['request_type'],
                    'company_id' => $providerQuotationInfo['company_id'],
                ]
            );
            $this->syncProvider($providerQuotation, $providerQuotationInfo);
            $this->syncCostCenter($providerQuotation, $providerQuotationInfo);

            if (array_key_exists('request_ids', $providerQuotationInfo)) {
                foreach ($providerQuotationInfo['request_ids'] as $request_id) {
                    PurchaseRequest::where('id', $request_id)->update(['status' => 1]);
                }
            }

            return $this->providerQuotation->with($this->with)->findOrFail($providerQuotation->id);
        } else {
            return response()->json([
                'error' => 'Deve ter pelo menos uma cotação.',
            ], 422);
        }
    }

    public function putProviderQuotation($id, $providerQuotationInfo, Request $request)
    {
        $providerQuotation = $this->providerQuotation->findOrFail($id);

        $providerQuotation->fill($providerQuotationInfo)->save();

        $this->putProvider($id, $providerQuotationInfo);

        return $this->providerQuotation->with($this->with)->findOrFail($providerQuotation->id);
    }

    public function syncCostCenter($providerQuotation, $providerQuotationInfo)
    {
        if (array_key_exists('cost_centers', $providerQuotationInfo)) {
            foreach ($providerQuotationInfo['cost_centers'] as $costCenter) {
                $providerQuotationCostCenter = new ProviderQuotationHasCostCenters();
                $costCenter['provider_quotation_id'] = $providerQuotation->id;
                $costCenter['cost_center_id'] = $costCenter['cost_center_id'];
                $costCenter['percentage'] = $costCenter['percentage'];
                $providerQuotationCostCenter->create($costCenter);
            }
        }
    }

    public function syncProvider($providerQuotation, $providerQuotationInfo)
    {
        if (array_key_exists('quotation_items', $providerQuotationInfo)) {
            foreach ($providerQuotationInfo['quotation_items'] as $item) {
                $providerQuotationItem = new ProviderQuotationItems;
                $item['provider_quotation_id'] = $providerQuotation->id;
                $item['provider_id'] = $item['provider_id'];
                $providerQuotationItem = $providerQuotationItem->create($item);

                $this->syncProducts($providerQuotationItem, $item);
                $this->syncServices($providerQuotationItem, $item);
            }
        }
    }

    public function putProvider($id, $providerQuotationInfo)
    {
        $updateQuotationItem = [];
        $createdQuotationItem = [];
        if (array_key_exists('quotation_items', $providerQuotationInfo)) {
            foreach ($providerQuotationInfo['quotation_items'] as $item) {
                $providerQuotationItem = new ProviderQuotationItems;
                if (array_key_exists('id', $item) && $item['id'] != null) {
                    $providerQuotationItem = $providerQuotationItem->where([
                        'id' => $item['id'],
                        'provider_quotation_id' => $id,
                        'provider_id' => $item['provider_id']
                    ])->firstOrFail();
                    $providerQuotationItem->fill([
                        'selected_products' => $item['selected_products'],
                        'selected_services' => $item['selected_services']
                    ])->save();
                    $updateQuotationItem[] = $providerQuotationItem['id'];
                } else {
                    $providerQuotationItem = $providerQuotationItem->create([
                        'provider_quotation_id' => $id,
                        'provider_id' => $item['provider_id'],
                        'selected_products' => $item['selected_products'],
                        'selected_services' => $item['selected_services']
                    ]);
                    $createdQuotationItem[] = $providerQuotationItem['id'];
                }
                $this->putProducts($providerQuotationItem['id'], $item);
                $this->putServices($providerQuotationItem['id'], $item);
            }
        }

        $collection = $providerQuotationItem->where('provider_quotation_id', $id)->whereNotIn('id', $updateQuotationItem)->whereNotIn('id', $createdQuotationItem)->get(['id']);
        $providerQuotationItem->destroy($collection->toArray());
    }

    public function syncProducts($providerQuotationItem, $item)
    {
        if (array_key_exists('products', $item)) {
            foreach ($item['products'] as $product) {
                $providerQuotationHasproducts = new ProviderQuotationHasproducts;
                $providerQuotationHasproducts = $providerQuotationHasproducts->create([
                    'provider_quotation_item_id' => $providerQuotationItem->id,
                    'product_id' => $product['product_id'],
                    'quantity' => $product['quantity'],
                    'quantity_request' => $product['quantity_request'],
                    'unit_price' => $product['unit_price'],
                    'total_without_discount' => $product['total_without_discount'],
                    'discount' => $product['discount'],
                    'total_discount' => $product['total_discount'],
                    'observations' => $product['observations'],
                ]);
            }
        }
    }

    public function putProducts($id, $providerQuotationInfo)
    {
        if (array_key_exists('products', $providerQuotationInfo)) {
            foreach ($providerQuotationInfo['products'] as $product) {
                $providerQuotationHasProducts = new ProviderQuotationHasProducts();
                if (array_key_exists('id', $product) && $product['id'] != null) {
                    $providerQuotationHasProducts = $this->providerQuotationHasProducts->where([
                        'id' => $product['id'],
                        'provider_quotation_item_id' => $id,
                        'product_id' => $product['product_id'],
                    ])->firstOrFail();
                    $providerQuotationHasProducts->fill($product)->save();
                } else {

                    $providerQuotationHasProducts = $providerQuotationHasProducts->create([
                        'provider_quotation_item_id' => $id,
                        'product_id' => $product['product_id'],
                        'quantity' => $product['quantity'],
                        'quantity_request' => $product['quantity_request'],
                        'unit_price' => $product['unit_price'],
                        'total_without_discount' => $product['total_without_discount'],
                        'discount' => $product['discount'],
                        'total_discount' => $product['total_discount'],
                        'observations' => $product['observations'],
                    ]);
                }
            }
        }
    }

    public function syncServices($providerQuotationItem, $item)
    {
        if (array_key_exists('services', $item)) {
            foreach ($item['services'] as $service) {
                $providerQuotationHasServices = new ProviderQuotationHasServices;
                $providerQuotationHasServices = $providerQuotationHasServices->create([
                    'provider_quotation_item_id' => $providerQuotationItem->id,
                    'service_id' => $service['service_id'],
                    'contract_duration' => $service['contract_duration'],
                    'quantity' => $service['quantity'],
                    'unit_price' => $service['unit_price'],
                    'total_without_discount' => $service['total_without_discount'],
                    'discount' => $service['discount'],
                    'total_discount' => $service['total_discount'],
                    'observations' => $service['observations'],
                ]);
            }
        }
    }

    public function putServices($id, $providerQuotationInfo)
    {
        if (array_key_exists('services', $providerQuotationInfo)) {
            foreach ($providerQuotationInfo['services'] as $service) {
                $providerQuotationHasServices = new ProviderQuotationHasServices();
                if (array_key_exists('id', $service) && $service['id'] != null) {
                    $providerQuotationHasServices = $this->providerQuotationHasServices->where([
                        'id' => $service['id'],
                        'provider_quotation_item_id' => $id,
                        'service_id' => $service['service_id'],
                    ])->firstOrFail();
                    $providerQuotationHasServices->fill($service)->save();
                } else {
                    $providerQuotationHasServices = $providerQuotationHasServices->create([
                        'provider_quotation_item_id' => $id,
                        'service_id' => $service['service_id'],
                        'contract_duration' => $service['contract_duration'],
                        'quantity' => $service['quantity'],
                        'unit_price' => $service['unit_price'],
                        'total_without_discount' => $service['total_without_discount'],
                        'discount' => $service['discount'],
                        'total_discount' => $service['total_discount'],
                        'observations' => $service['observations'],
                    ]);
                }
            }
        }
    }

    public function deleteProviderQuotation($id)
    {
        $pq_id = $this->providerQuotation->findOrFail($id);
        $this->providerQuotation->findOrFail($id)->delete();
        if ($pq_id->request_ids != null) {
            foreach ($pq_id->request_ids as $request_id) {
                PurchaseRequest::where('id', $request_id)->update(['status' => 0]);
            }
        }
        return true;
    }
}
