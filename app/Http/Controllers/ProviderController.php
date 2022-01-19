<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ProviderService as ProviderService;
use App\Http\Requests\StoreProviderRequest;
use App\Http\Requests\PutProviderRequest;
use App\Imports\ProvidersImport;

class ProviderController extends Controller
{
    private $providerService;
    private $providerImport;

    public function __construct(ProviderService $providerService, ProvidersImport $providerImport)
    {
        $this->providerService = $providerService;
        $this->providerImport = $providerImport;
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
        $user = auth()->user();
        return $this->providerService->postProvider($user->id, $request->all());
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

    public function import()
    {
        $this->providerImport->import(request()->file('import_file'));
        return response('');
    }
}
