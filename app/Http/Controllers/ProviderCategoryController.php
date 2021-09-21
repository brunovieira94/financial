<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Services\ProviderCategoryService as ProviderCategoryService;
use App\Http\Requests\StoreProviderCategoryRequest;


class ProviderCategoryController extends Controller
{
    private $providerCategoryService;

    public function __construct(ProviderCategoryService $providerCategoryService)
    {
        $this->providerCategoryService = $providerCategoryService;
    }

    public function index(Request $request)
    {
        return $this->providerCategoryService->getAllProviderCategory($request->all());
    }

    public function show($id)
    {
        return $this->providerCategoryService->getProviderCategory($id);
    }

    public function store(StoreProviderCategoryRequest $request)
    {
        return $this->providerCategoryService->postProviderCategory($request->all());
    }

    public function update(StoreProviderCategoryRequest $request, $id)
    {
        return $this->providerCategoryService->putProviderCategory($id, $request->all());
    }

    public function destroy($id)
    {
        $paymentType = $this->providerCategoryService->deleteProviderCategory($id);
        return response('');
    }

}
