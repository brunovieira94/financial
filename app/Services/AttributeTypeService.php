<?php

namespace App\Services;
use App\Models\AttributeType;
use Attribute;

class AttributeTypeService
{
    private $attributeType;
    public function __construct(AttributeType $attributeType)
    {
        $this->attributeType = $attributeType;
    }

    public function getAllAttributeType($requestInfo)
    {
        $attributeType = Utils::search($this->attributeType,$requestInfo);
        return Utils::pagination($attributeType,$requestInfo);
    }

    public function getAttributeType($id)
    {
      return $this->attributeType->findOrFail($id);
    }

    public function postAttributeType($attributeTypeInfo)
    {
        $attributeType = new AttributeType;
        return $attributeType->create($attributeTypeInfo);
    }

    public function putAttributeType($id, $attributeTypeInfo)
    {
        $attributeType = $this->attributeType->findOrFail($id);
        $attributeType->fill($attributeTypeInfo)->save();
        return $attributeType;
    }

    public function deleteAttributeType($id)
    {
      $this->attributeType->findOrFail($id)->delete();
      return true;
    }

}
