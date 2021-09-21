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

    public function getAllProviderCategory($requestInfo)
    {
        $orderBy = $requestInfo['orderBy'] ?? Utils::defaultOrderBy;
        $order = $requestInfo['order'] ?? Utils::defaultOrder;
        $perPage = $requestInfo['perPage'] ?? Utils::defaultPerPage;
        return $this->providerCategory->orderBy($orderBy, $order)->paginate($perPage);
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
