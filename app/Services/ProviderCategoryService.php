<?php

namespace App\Services;
use App\Models\ProviderCategorie;

class ProviderCategorieService
{
    private $providerCategorie;
    public function __construct(ProviderCategorie $providerCategorie)
    {
        $this->providerCategorie = $providerCategorie;
    }

    public function getAllProviderCategorie()
    {
        return $this->providerCategorie->get();
    }

    public function getProviderCategorie($id)
    {
      return $this->providerCategorie->findOrFail($id);
    }

    public function postProviderCategorie($providerCategorieInfo)
    {
        $providerCategorie = new ProviderCategorie;
        return $providerCategorie->create($providerCategorieInfo);
    }

    public function putProviderCategorie($id, $providerCategorieInfo)
    {
        $providerCategorie = $this->providerCategorie->findOrFail($id);
        $providerCategorie->fill($providerCategorieInfo)->save();
        return $providerCategorie;
    }

    public function deleteProviderCategorie($id)
    {
      $this->providerCategorie->findOrFail($id)->delete();
      return true;
    }

}
