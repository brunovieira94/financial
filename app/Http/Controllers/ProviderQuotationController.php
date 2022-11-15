<?php

namespace App\Http\Controllers;

use App\Exports\InfoQuotationExport;
use App\Http\Requests\StoreProviderQuotationRequest;
use App\Http\Requests\PutProviderQuotationRequest;
use App\Models\ProviderQuotation;
use App\Services\ProviderQuotationService as ProviderQuotationService;
use Illuminate\Http\Request;

class ProviderQuotationController extends Controller
{

    private $providerQuotationService;

    public function __construct(ProviderQuotationService $providerQuotationService)
    {
        $this->providerQuotationService = $providerQuotationService;
    }

    public function index(Request $request)
    {
        return $this->providerQuotationService->getAllProviderQuotation($request->all());
    }

    public function show($id)
    {
        return $this->providerQuotationService->getProviderQuotation($id);
    }

    public function store(StoreProviderQuotationRequest $request)
    {
        return $this->providerQuotationService->postProviderQuotation($request->all(), $request);
    }

    public function update(PutProviderQuotationRequest $request, $id)
    {
        return $this->providerQuotationService->putProviderQuotation($id, $request->all(), $request);
    }

    public function destroy($id)
    {
        $this->providerQuotationService->deleteProviderQuotation($id);
        return response('');
    }

    public function export(Request $request)
    {
        if (array_key_exists('exportFormat', $request->all())) {
            if ($request->all()['exportFormat'] == 'csv') {
                return (new InfoQuotationExport($request->all()))->download('cotacao.csv', \Maatwebsite\Excel\Excel::CSV, ['Content-Type' => 'text/csv']);
            }
        }
        return (new InfoQuotationExport($request->all()))->download('cotacao.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }
}
