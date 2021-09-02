<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreBankRequest;
use App\Services\BankService as BankService;

class BankController extends Controller
{
    private $bankService;
    public function __construct(BankService $bankService)
    {
        $this->bankService = $bankService;
    }

    public function index()
    {
        return $this->bankService->getAllBank();
    }

    public function show($id)
    {
        return $this->bankService->getBank($id);
    }

    public function store(StoreBankRequest $request)
    {
        return $this->bankService->postBank($request->all());
    }

    public function update(StoreBankRequest $request, $id)
    {
        return $this->bankService->putBank($id, $request->all());
    }

    public function destroy($id)
    {
       $bank = $this->bankService->deleteBank($id);
       return response('');
    }
}
