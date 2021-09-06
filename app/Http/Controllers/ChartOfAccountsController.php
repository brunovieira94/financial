<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreChartOfAccountsRequest;
use App\Http\Requests\PutChartOfAccountsRequest;
use App\Services\ChartOfAccountsService as ChartOfAccountsService;

class ChartOfAccountsController extends Controller
{

    private $chartOfAccountsService;

    public function __construct(ChartOfAccountsService $chartOfAccountsService)
    {
        $this->chartOfAccountsService = $chartOfAccountsService;
    }

    public function index()
    {
        return $this->chartOfAccountsService->getAllChartOfAccounts();
    }

    public function show($id)
    {
        return $this->chartOfAccountsService->getChartOfAccounts($id);
    }

    public function store(StoreChartOfAccountsRequest $request)
    {
        $chartOfAccounts = $this->chartOfAccountsService->postChartOfAccounts($request->all());
        return response($chartOfAccounts, 201);
    }

    public function update(PutChartOfAccountsRequest $request, $id)
    {
        return $this->chartOfAccountsService->putChartOfAccounts($id, $request->all());
        return response($chartOfAccounts);
    }

    public function destroy($id)
    {
        $chartOfAccounts = $this->chartOfAccountsService->deleteChartOfAccounts($id);
        return response('');
    }
}