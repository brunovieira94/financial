<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Services\IntegrationService;

class IntegrationController extends Controller
{
    private $integrationService;

    public function __construct(IntegrationService $integrationService)
    {
        $this->integrationService = $integrationService;
    }

    public function sapGetApprovedBills(Request $request)
    {
        return $this->integrationService->sapBillsApproved($request->all());
    }

    public function sapGetPaidInstallments(Request $request)
    {
        return $this->integrationService->sapInstallmentsPaid($request->all());
    }

    public function storeClient(Request $request)
    {
        return $this->integrationService->storeClient($request->all());
    }

    public function getAllClient(Request $request)
    {
        return $this->integrationService->getAllClient($request->all());
    }

    public function getClient($id)
    {
        return $this->integrationService->getClient($id);
    }

    public function updateClient(Request $request, $id)
    {
        return $this->integrationService->updateClient($request->all(), $id);
    }

    public function deleteClient($id)
    {
        return $this->integrationService->deleteClient($id);
    }
}
