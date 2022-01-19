<?php

namespace App\Http\Controllers;

use App\Imports\ServicesImport;
use Illuminate\Http\Request;
use App\Http\Requests\StoreServiceRequest;
use App\Services\ServiceService as ServiceService;

class ServiceController extends Controller
{

    private $serviceService;

    public function __construct(ServiceService $serviceService)
    {
        $this->serviceService = $serviceService;
    }

    public function index(Request $request)
    {
        return $this->serviceService->getAllService($request->all());
    }

    public function show($id)
    {
        return $this->serviceService->getService($id);
    }

    public function store(StoreServiceRequest $request)
    {
        return $this->serviceService->postService($request->all());
    }

    public function update(StoreServiceRequest $request, $id)
    {
        return $this->serviceService->putService($id, $request->all());
    }

    public function destroy($id)
    {
        $service = $this->serviceService->deleteService($id);
        return response('');
    }

    public function import()
    {
        (new ServicesImport)->import(request()->file('import_file'));
        return response('');
    }
}
