<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ProviderService as ProviderService;
use App\Http\Requests\StoreProviderRequest;
use App\Http\Requests\PutProviderRequest;
use App\Imports\ProvidersImport;
use App\Exports\ProvidersExport;
use App\Models\PaymentRequest;
use App\Models\Provider;
use App\Models\PurchaseOrder;

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
        return $this->providerService->postProvider($request);
    }

    public function update(PutProviderRequest $request, $id)
    {
        return $this->providerService->putProvider($id, $request);
    }

    public function destroy($id)
    {
        if(PaymentRequest::where('provider_id', $id)->exists())
        {
            return response()->json([
                'error' => 'Este fornecedor está associado a uma ou várias solicitações de pagamento.'
            ], 422);
        }
        if(PurchaseOrder::where('provider_id', $id)->exists())
        {
            return response()->json([
                'error' => 'Este fornecedor está associado a um pedido de compra.'
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
