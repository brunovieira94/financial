<?php

namespace App\Services;
use App\Models\Product;
use App\Models\ProductsHasAttributes;

class ProductService
{
    private $product;
    private $with = ['chart_of_account', 'measurement_unit', 'attributes'];
    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    public function getAllProduct($requestInfo)
    {
        $product = Utils::search($this->product,$requestInfo);
        return Utils::pagination($product->with($this->with),$requestInfo);
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
        $this->syncAttributes($product, $productInfo);
        return $this->product->with($this->with)->findOrFail($product->id);
    }

    public function deleteProduct($id)
    {
      $this->product->findOrFail($id)->delete();
      return true;
    }

    public function syncAttributes($product, $productInfo){
        $syncArray = [];
        if(array_key_exists('attributes', $productInfo)){
            foreach($productInfo['attributes'] as $attribute){
                // $syncArray[$attribute['id']] = [];
                // $syncArray[$attribute['id']]['value'] = $attribute['value'];
                $productsHasAttributes = new ProductsHasAttributes;
                $productsHasAttributes = $productsHasAttributes->create([
                    'product_id' => $product->id,
                    'attribute_id' => $attribute['id'],
                    'value' => $attribute['value'],
                ]);
            }
        }
    }

}
