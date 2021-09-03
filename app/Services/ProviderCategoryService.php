<?php

namespace App\Services;
use App\Models\ProviderCategory;

class ProviderCategoryService
{
    private $providerCategory;
    public function __construct(ProviderCategory $providerCategory)
    {
        $this->providerCategory = $providerCategory;
    }

    public function getAllProviderCategory()
    {
        return $this->providerCategory->get();
    }

    public function getProviderCategory($id)
    {
      return $this->providerCategory->findOrFail($id);
    }

    public function postProviderCategory($providerCategoryInfo)
    {
        $providerCategory = new ProviderCategory;
        return $providerCategory->create($providerCategoryInfo);
    }

    public function putProviderCategory($id, $providerCategoryInfo)
    {
        $providerCategory = $this->providerCategory->findOrFail($id);
        $providerCategory->fill($providerCategoryInfo)->save();
        return $providerCategory;
    }

    public function deleteProviderCategory($id)
    {
      $this->providerCategory->findOrFail($id)->delete();
      return true;
    }

}
