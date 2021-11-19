<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreAttributeTypeRequest;
use App\Services\AttributeTypeService as AttributeTypeService;

class AttributeTypeController extends Controller
{

    private $attributeTypeService;

    public function __construct(AttributeTypeService $attributeTypeService)
    {
        $this->attributeTypeService = $attributeTypeService;
    }

    public function index(Request $request)
    {
        return $this->attributeTypeService->getAllAttributeType($request->all());
    }

    public function show($id)
    {
        return $this->attributeTypeService->getAttributeType($id);
    }

    public function store(StoreAttributeTypeRequest $request)
    {
        $attributeType = $this->attributeTypeService->postAttributeType($request->all());
        return response($attributeType, 201);
    }

    public function update(StoreAttributeTypeRequest $request, $id)
    {
        return $this->attributeTypeService->putAttributeType($id, $request->all());
    }

    public function destroy($id)
    {
        $attributeType = $this->attributeTypeService->deleteAttributeType($id);
        return response('');
    }
}
