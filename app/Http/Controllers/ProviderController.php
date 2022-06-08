<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ProviderService as ProviderService;
use App\Http\Requests\StoreProviderRequest;
use App\Http\Requests\PutProviderRequest;
use App\Imports\ProvidersImport;
use App\Exports\ProvidersExport;
use App\Models\Provider;

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
        return $this->providerService->postProvider($request->all());
    }

    public function update(PutProviderRequest $request, $id)
    {
        return $this->providerService->putProvider($id, $request->all());
    }

    public function destroy($id)
    {
        if(Provider::where('provider_id', $id)->exists())
        {
            return response()->json([
                'erro' => 'Este fornecedor está associado a uma ou várias solicitações de pagamento.'
            ], 422);
        }

        $provider = $this->providerService->deleteProvider($id);
        return response('');
    }

    public function import()
    {
        $this->providerImport->import(request()->file('import_file'));
        return response('');
    }

    public function export(Request $request)
    {
        if(array_key_exists('exportFormat', $request->all()))
        {
            if($request->all()['exportFormat'] == 'csv')
            {
                return (new ProvidersExport($request->all()))->download('fornecedores.csv', \Maatwebsite\Excel\Excel::CSV, ['Content-Type' => 'text/csv']);
            }
        }
        return (new ProvidersExport($request->all()))->download('fornecedores.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }
}
