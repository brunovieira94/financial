<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductHasAttributes;

class ProductService
{
    private $product;
    private $productHasAttributes;
    private $with = ['chart_of_account', 'measurement_unit', 'attributes'];
    public function __construct(Product $product, ProductHasAttributes $productHasAttributes)
    {
        $this->product = $product;
        $this->productHasAttributes = $productHasAttributes;
    }

    public function getAllProduct($requestInfo)
    {
        if (array_key_exists('search', $requestInfo)) {
            if (strlen($requestInfo["search"]) >= 4) {
                if (substr($requestInfo["search"], 0, 3) == 500) {
                    $requestInfo['search'] = substr($requestInfo["search"], 3, strlen($requestInfo["search"]));
                } else if (substr($requestInfo["search"], 0, 2) > 50) {
                    $requestInfo['search'] =  substr($requestInfo["search"], 1, strlen($requestInfo["search"]));
                } else if (substr($requestInfo["search"], 0, 2) == 50) {
                    $requestInfo['search'] = substr($requestInfo["search"], 2, strlen($requestInfo["search"]));
                }
            }
        }

        $product = Utils::search($this->product, $requestInfo);
        return Utils::pagination($product->with($this->with), $requestInfo);
    }

    public function getProduct($id)
    {
        return $this->product->with($this->with)->findOrFail($id);
    }

    public function postProduct($productInfo)
    {
        $product = new Product;
        $product = $product->create($productInfo);
        $this->syncAttributes($product, $productInfo);
        return $this->product->with($this->with)->findOrFail($product->id);
    }

    public function putProduct($id, $productInfo)
    {
        $product = $this->product->findOrFail($id);
        $product->fill($productInfo)->save();
        $this->putAttributes($id, $productInfo);
        return $this->product->with($this->with)->findOrFail($product->id);
    }

    public function deleteProduct($id)
    {
        $this->product->findOrFail($id)->delete();
        return true;
    }

    public function syncAttributes($product, $productInfo)
    {
        if (array_key_exists('attributes', $productInfo)) {
            foreach ($productInfo['attributes'] as $attribute) {
                $productHasAttributes = new ProductHasAttributes;
                $productHasAttributes = $productHasAttributes->create([
                    'product_id' => $product->id,
                    'attribute_id' => $attribute['attribute_id'],
                    'value' => $attribute['value'],
                ]);
            }
        }
    }

    public function putAttributes($id, $productInfo)
    {

        $updateAttributes = [];
        $createdAttributes = [];

        if (array_key_exists('attributes', $productInfo)) {
            foreach ($productInfo['attributes'] as $attribute) {
                if (array_key_exists('id', $attribute)) {
                    $productHasAttributes = $this->productHasAttributes->findOrFail($attribute['id']);
                    $productHasAttributes->fill($attribute)->save();
                    $updateAttributes[] = $attribute['id'];
                } else {
                    $productHasAttributes = $this->productHasAttributes->create([
                        'product_id' => $id,
                        'attribute_id' => $attribute['attribute_id'],
                        'value' => $attribute['value'],
                    ]);
                    $createdAttributes[] = $productHasAttributes->id;
                }
            }

            $collection = $this->productHasAttributes->where('product_id', $id)->whereNotIn('id', $updateAttributes)->whereNotIn('id', $createdAttributes)->get(['id']);
            $this->productHasAttributes->destroy($collection->toArray());
        }
    }
}
