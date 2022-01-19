<?php

namespace App\Services;
use App\Models\TypeOfTax;

class TypeOfTaxService
{
    private $typeOfTax;
    public function __construct(TypeOfTax $typeOfTax)
    {
        $this->typeOfTax = $typeOfTax;
    }

    public function getAllTypeOfTax($requestInfo)
    {
        $typeOfTax = Utils::search($this->typeOfTax,$requestInfo);
        return Utils::pagination($typeOfTax,$requestInfo);
    }

    public function getTypeOfTax($id)
    {
      return $this->typeOfTax->findOrFail($id);
    }

    public function postTypeOfTax($typeOfTaxInfo)
    {
        $typeOfTax = new TypeOfTax;
        $typeOfTax = $typeOfTax->create($typeOfTaxInfo);
        return $this->typeOfTax->findOrFail($typeOfTax->id);
    }

    public function putTypeOfTax($id, $typeOfTaxInfo)
    {
        $typeOfTax = $this->typeOfTax->findOrFail($id);
        $typeOfTax->fill($typeOfTaxInfo)->save();
        return $this->typeOfTax->findOrFail($typeOfTax->id);
    }

    public function deleteTypeOfTax($id)
    {
      $this->typeOfTax->findOrFail($id)->delete();
      return true;
    }

}
