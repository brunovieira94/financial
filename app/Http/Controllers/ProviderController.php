<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ProviderService as ProviderService;
use App\Http\Requests\StoreProviderRequest;
use App\Http\Requests\PutProviderRequest;

class ProviderController extends Controller
{
    private $providerService;

    public function __construct(ProviderService $providerService)
    {
        $this->providerService = $providerService;
    }

    public function index(Request $request)
    {
        return $this->providerService->getAllProvider($request->all());
    }

    public function show($id)
    {
        return $this->providerService->getProvider($id);
    }

    public function store(StoreProviderRequest $request)
    {
        return $this->providerService->postProvider($request->all());
    }

    public function update(PutProviderRequest $request, $id)
    {
        return $this->providerService->putProvider($id, $request->all());
    }

    public function destroy($id)
    {
        $provider = $this->providerService->deleteProvider($id);
        return response('');
    }
}
