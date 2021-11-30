<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\PutProductRequest;
use App\Services\ProductService as ProductService;

class ProductController extends Controller
{

    private $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index(Request $request)
    {
        return $this->productService->getAllProduct($request->all());
    }

    public function show($id)
    {
        return $this->productService->getProduct($id);
    }

    public function store(StoreProductRequest $request)
    {
        return $this->productService->postProduct($request->all());
    }

    public function update(PutProductRequest $request, $id)
    {
        return $this->productService->putProduct($id, $request->all());
    }

    public function destroy($id)
    {
        $product = $this->productService->deleteProduct($id);
        return response('');
    }

}
